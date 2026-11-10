<?php

namespace App\Http\Controllers;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Semester;
use App\Models\User;
use App\Services\DepartmentCourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentCourseController extends Controller
{
    public function __construct(private readonly DepartmentCourseService $courseService) {}

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
        $this->courseService->createCourse($data, (int) $departmentId);

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
        $this->courseService->updateCourse($course, $data);

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
        $this->courseService->createOffering($course, $data);

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
        $this->courseService->updateOffering($offering, $data);

        return back()->with('status', 'Course offering updated.');
    }

    public function assignLecturer(Request $request, Course $course): RedirectResponse
    {
        $course = $this->coursesForDepartment()->findOrFail($course->id);
        $data = $request->validate(['lecturer_id' => ['required', 'integer', 'exists:users,id']]);
        $lecturer = User::role('Lecturer')->where('department_id', $course->department_id)->findOrFail($data['lecturer_id']);
        $this->courseService->assignLecturer($course, $lecturer);

        return back()->with('status', 'Lecturer assigned to course.');
    }

    public function unassignLecturer(Request $request, Course $course, User $lecturer): RedirectResponse
    {
        $course = $this->coursesForDepartment()->findOrFail($course->id);
        $lecturer = User::role('Lecturer')->where('department_id', $course->department_id)->findOrFail($lecturer->id);
        $this->courseService->unassignLecturer($course, $lecturer);

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
