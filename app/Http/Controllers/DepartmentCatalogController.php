<?php

namespace App\Http\Controllers;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Semester;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentCatalogController extends Controller
{
    public function academics(): View
    {
        $user = auth()->user();
        $departmentId = $user->hasRole('Super Admin') ? null : $user->department_id;

        return view('admin.academics', [
            'departments' => $user->hasRole('Super Admin') ? Department::orderBy('name')->get() : collect(),
            'programs' => AcademicProgram::with(['department', 'levels'])
                ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
                ->orderBy('name')->get(),
            'levels' => AcademicLevel::with('department')
                ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
                ->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function storeAcademicLevel(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $data = $request->validate([
            'department_id' => [$isSuperAdmin ? 'required' : 'prohibited', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);
        $departmentId = $isSuperAdmin ? $data['department_id'] : $user->department_id;
        $level = AcademicLevel::create([...$data, 'department_id' => $departmentId, 'active' => true]);
        app(ActivityLogger::class)->record('academic_level.created', $level, ['department_id' => $departmentId]);

        return back()->with('status', 'Academic level created.');
    }

    public function updateAcademicLevel(Request $request, AcademicLevel $level): RedirectResponse
    {
        $level = $this->academicLevelsForDepartment()->findOrFail($level->id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'active' => ['required', 'boolean'],
        ]);
        $level->update($data);
        app(ActivityLogger::class)->record('academic_level.updated', $level, $data);

        return back()->with('status', 'Academic level updated.');
    }

    public function storeProgram(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $departmentId = $isSuperAdmin ? $request->input('department_id') : $user->department_id;
        $data = $request->validate([
            'department_id' => [$isSuperAdmin ? 'required' : 'prohibited', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'alpha_dash:ascii', Rule::unique('academic_programs')->where('department_id', $departmentId)],
            'academic_level_ids' => ['sometimes', 'array'],
            'academic_level_ids.*' => ['integer', Rule::exists('academic_levels', 'id')->where('department_id', $departmentId)],
        ]);
        $program = AcademicProgram::create([
            'department_id' => $departmentId,
            'name' => $data['name'],
            'code' => $data['code'],
            'active' => true,
        ]);
        $program->levels()->sync($data['academic_level_ids'] ?? []);
        app(ActivityLogger::class)->record('program.created', $program, ['level_ids' => $data['academic_level_ids'] ?? []]);

        return back()->with('status', 'Academic program created.');
    }

    public function updateProgram(Request $request, AcademicProgram $program): RedirectResponse
    {
        $program = $this->academicProgramsForDepartment()->findOrFail($program->id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'alpha_dash:ascii', Rule::unique('academic_programs')->where('department_id', $program->department_id)->ignore($program->id)],
            'active' => ['required', 'boolean'],
            'academic_level_ids' => ['sometimes', 'array'],
            'academic_level_ids.*' => ['integer', Rule::exists('academic_levels', 'id')->where('department_id', $program->department_id)],
        ]);
        $program->update(collect($data)->except('academic_level_ids')->all());
        $program->levels()->sync($data['academic_level_ids'] ?? []);
        app(ActivityLogger::class)->record('program.updated', $program, ['level_ids' => $data['academic_level_ids'] ?? []]);

        return back()->with('status', 'Academic program updated.');
    }

    public function courses(): View
    {
        $user = auth()->user();

        return view('admin.courses.index', [
            'courses' => $this->coursesForDepartment()->with(['department', 'lecturers'])->orderBy('code')->get(),
            'offerings' => CourseOffering::with(['course', 'semester.academicSession', 'program', 'academicLevel'])
                ->whereHas('course', fn ($query) => $query->when(
                    ! $user->hasRole('Super Admin'), fn ($courses) => $courses->where('department_id', $user->department_id),
                ))
                ->latest()->get(),
            'lecturers' => $this->usersForRole('Lecturer')->orderBy('name')->get(),
            'departments' => $user->hasRole('Super Admin') ? Department::orderBy('name')->get() : collect(),
            'programs' => $this->academicProgramsForDepartment()->where('active', true)->orderBy('name')->get(),
            'levels' => $this->academicLevelsForDepartment()->where('active', true)->orderBy('sort_order')->get(),
            'semesters' => Semester::with('academicSession')->orderByDesc('starts_at')->get(),
        ]);
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $departmentId = $isSuperAdmin ? $request->input('department_id') : $user->department_id;
        $data = $request->validate([
            'department_id' => [$isSuperAdmin ? 'required' : 'prohibited', 'integer', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:30', Rule::unique('courses')->where('department_id', $departmentId)],
            'title' => ['required', 'string', 'max:255'],
            'credit_units' => ['required', 'integer', 'between:1,10'],
        ]);
        $course = Course::create([
            'department_id' => $departmentId,
            'code' => $data['code'],
            'title' => $data['title'],
            'credit_units' => $data['credit_units'],
            'active' => true,
        ]);
        app(ActivityLogger::class)->record('course.created', $course);

        return back()->with('status', 'Course created.');
    }

    public function updateCourse(Request $request, Course $course): RedirectResponse
    {
        $course = $this->coursesForDepartment()->findOrFail($course->id);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('courses')->where('department_id', $course->department_id)->ignore($course->id)],
            'title' => ['required', 'string', 'max:255'],
            'credit_units' => ['required', 'integer', 'between:1,10'],
            'active' => ['required', 'boolean'],
        ]);
        $course->update($data);
        app(ActivityLogger::class)->record('course.updated', $course, $data);

        return back()->with('status', 'Course updated.');
    }

    public function storeOffering(Request $request, Course $course): RedirectResponse
    {
        $course = $this->coursesForDepartment()->findOrFail($course->id);
        $data = $request->validate([
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'academic_program_id' => ['nullable', 'integer', Rule::exists('academic_programs', 'id')->where('department_id', $course->department_id)],
            'academic_level_id' => ['nullable', 'integer', Rule::exists('academic_levels', 'id')->where('department_id', $course->department_id)],
        ]);
        if ($data['academic_program_id'] && $data['academic_level_id'] && ! AcademicProgram::find($data['academic_program_id'])
            ->levels()->whereKey($data['academic_level_id'])->exists()) {
            return back()->withErrors(['academic_level_id' => 'The selected level is not offered by this program.']);
        }
        $duplicate = CourseOffering::where('course_id', $course->id)
            ->where('semester_id', $data['semester_id'])
            ->where('academic_program_id', $data['academic_program_id'])
            ->where('academic_level_id', $data['academic_level_id'])
            ->exists();
        if ($duplicate) {
            return back()->withErrors(['semester_id' => 'This course offering already exists.']);
        }

        $offering = CourseOffering::create(['course_id' => $course->id, ...$data, 'active' => true]);
        app(ActivityLogger::class)->record('course_offering.created', $offering, $data);

        return back()->with('status', 'Course offering created.');
    }

    public function updateOffering(Request $request, CourseOffering $offering): RedirectResponse
    {
        $offering = CourseOffering::whereKey($offering->id)
            ->whereHas('course', fn ($query) => $query->when(
                ! $request->user()->hasRole('Super Admin'), fn ($courses) => $courses->where('department_id', $request->user()->department_id),
            ))
            ->firstOrFail();
        $data = $request->validate(['active' => ['required', 'boolean']]);
        $offering->update($data);
        app(ActivityLogger::class)->record('course_offering.updated', $offering, $data);

        return back()->with('status', 'Course offering updated.');
    }

    public function assignLecturer(Request $request, Course $course): RedirectResponse
    {
        $course = $this->coursesForDepartment()->findOrFail($course->id);
        $data = $request->validate(['lecturer_id' => ['required', 'integer', 'exists:users,id']]);
        $lecturer = User::role('Lecturer')->where('department_id', $course->department_id)->findOrFail($data['lecturer_id']);
        $course->lecturers()->syncWithoutDetaching([$lecturer->id]);
        app(ActivityLogger::class)->record('lecturer.assigned_to_course', $course, ['lecturer_id' => $lecturer->id]);

        return back()->with('status', 'Lecturer assigned to course.');
    }

    public function unassignLecturer(Request $request, Course $course, User $lecturer): RedirectResponse
    {
        $course = $this->coursesForDepartment()->findOrFail($course->id);
        $lecturer = User::role('Lecturer')->where('department_id', $course->department_id)->findOrFail($lecturer->id);
        $course->lecturers()->detach($lecturer->id);
        app(ActivityLogger::class)->record('lecturer.unassigned_from_course', $course, ['lecturer_id' => $lecturer->id]);

        return back()->with('status', 'Lecturer removed from course.');
    }

    private function usersForRole(string $role)
    {
        $user = auth()->user();

        return User::role($role)->when(
            ! $user->hasRole('Super Admin'), fn ($query) => $query->where('department_id', $user->department_id),
        );
    }

    private function academicLevelsForDepartment()
    {
        $user = auth()->user();

        return AcademicLevel::query()->when(
            ! $user->hasRole('Super Admin'), fn ($query) => $query->where('department_id', $user->department_id),
        );
    }

    private function academicProgramsForDepartment()
    {
        $user = auth()->user();

        return AcademicProgram::query()->when(
            ! $user->hasRole('Super Admin'), fn ($query) => $query->where('department_id', $user->department_id),
        );
    }

    private function coursesForDepartment()
    {
        $user = auth()->user();

        return Course::query()->when(
            ! $user->hasRole('Super Admin'), fn ($query) => $query->where('department_id', $user->department_id),
        );
    }
}