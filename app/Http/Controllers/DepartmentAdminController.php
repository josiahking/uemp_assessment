<?php

namespace App\Http\Controllers;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Result;
use App\Models\ResultEvent;
use App\Models\Semester;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentAdminController extends Controller
{
    public function students(): View
    {
        $user = auth()->user();
        $students = $this->usersForRole('Student')
            ->with('department')
            ->orderBy('name')
            ->get();

        return view('admin.students.index', [
            'students' => $students,
            'departments' => $user->hasRole('Super Admin') ? Department::orderBy('name')->get() : collect(),
            'programs' => $user->hasRole('Super Admin') ? AcademicProgram::where('active', true)->orderBy('name')->get() : AcademicProgram::where('department_id', $user->department_id)->where('active', true)->orderBy('name')->get(),
            'levels' => $user->hasRole('Super Admin') ? AcademicLevel::where('active', true)->orderBy('sort_order')->get() : AcademicLevel::where('department_id', $user->department_id)->where('active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        return $this->storeUser($request, 'Student');
    }

    public function updateStudent(Request $request, User $student): RedirectResponse
    {
        $student = $this->usersForRole('Student')->findOrFail($student->id);
        $data = $this->validateUserProfile($request, $student->department_id, true);
        $student->update($data);
        app(ActivityLogger::class)->record('student.updated', $student, ['account_status' => $student->account_status]);

        return back()->with('status', 'Student profile updated.');
    }

    public function lecturers(): View
    {
        $user = auth()->user();

        return view('admin.lecturers.index', [
            'lecturers' => $this->usersForRole('Lecturer')->with('department')->orderBy('name')->get(),
            'departments' => $user->hasRole('Super Admin') ? Department::orderBy('name')->get() : collect(),
        ]);
    }

    public function storeLecturer(Request $request): RedirectResponse
    {
        return $this->storeUser($request, 'Lecturer');
    }

    public function updateLecturer(Request $request, User $lecturer): RedirectResponse
    {
        $lecturer = $this->usersForRole('Lecturer')->findOrFail($lecturer->id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($lecturer->id)],
            'faculty' => ['nullable', 'string', 'max:255'],
            'account_status' => ['required', Rule::in(['active', 'deactivated', 'suspended'])],
        ]);
        $lecturer->update($data);
        app(ActivityLogger::class)->record('lecturer.updated', $lecturer, ['account_status' => $lecturer->account_status]);

        return back()->with('status', 'Lecturer profile updated.');
    }

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

        $offering = CourseOffering::create([
            'course_id' => $course->id,
            ...$data,
            'active' => true,
        ]);
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
        $lecturer = User::role('Lecturer')
            ->where('department_id', $course->department_id)
            ->findOrFail($data['lecturer_id']);

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

    public function registrations(): View
    {
        $registrations = $this->registrationsForDepartment()
            ->latest()
            ->get();

        return view('admin.registrations.index', compact('registrations'));
    }

    public function updateRegistration(Request $request, CourseRegistration $registration): RedirectResponse
    {
        $registration = $this->registrationsForDepartment()->findOrFail($registration->id);

        abort_unless($registration->status === 'pending', 409);

        $data = $request->validate(['status' => ['required', Rule::in(['confirmed', 'rejected'])]]);
        DB::transaction(function () use ($registration, $data) {
            $registration->update(['status' => $data['status']]);
            app(ActivityLogger::class)->record('course_registration.'.$data['status'], $registration);
        });

        return back()->with('status', 'Registration '.$data['status'].'.');
    }

    public function results(): View
    {
        $results = $this->resultsForDepartment()->latest()->get();

        return view('admin.results.index', compact('results'));
    }

    public function reviewResult(Request $request, Result $result): RedirectResponse
    {
        $result = $this->resultsForDepartment()->findOrFail($result->id);

        abort_unless($result->status === 'submitted', 409);
        abort_if($result->submitted_by === $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'returned'])],
            'review_note' => ['required_if:status,rejected,returned', 'nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($result, $data, $request) {
            $result->update([
                'status' => $data['status'],
                'review_note' => $data['review_note'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
            ResultEvent::create([
                'result_id' => $result->id,
                'actor_id' => $request->user()->id,
                'event' => $data['status'],
                'from_status' => 'submitted',
                'to_status' => $data['status'],
                'metadata' => ['review_note' => $data['review_note'] ?? null],
            ]);
            app(ActivityLogger::class)->record('result.'.$data['status'], $result, ['review_note' => $data['review_note'] ?? null]);
        });

        return back()->with('status', 'Result '.$data['status'].'.');
    }

    public function reports(): View
    {
        $user = auth()->user();
        $departmentId = $user->hasRole('Super Admin') ? null : $user->department_id;
        $students = $this->usersForRole('Student')->count();
        $lecturers = $this->usersForRole('Lecturer')->count();
        $courses = $this->coursesForDepartment()->count();
        $registrations = $this->registrationsForDepartment();
        $results = $this->resultsForDepartment();
        $registrationRows = (clone $registrations)->get();
        $approvedResults = (clone $results)->where('status', 'approved')->get();
        $performance = (clone $results)->where('status', 'approved')
            ->with(['registration.student', 'registration.course'])
            ->orderByDesc('total_score')
            ->limit(10)
            ->get();

        return view('admin.reports.index', [
            'students' => $students,
            'lecturers' => $lecturers,
            'courses' => $courses,
            'pendingRegistrations' => $registrationRows->where('status', 'pending')->count(),
            'approvedResults' => $approvedResults->count(),
            'registrationSummary' => $registrationRows->countBy('status'),
            'gradeSummary' => $approvedResults->countBy('grade'),
            'performance' => $performance,
            'departmentName' => $departmentId ? $user->department?->name : 'All departments',
        ]);
    }

    private function storeUser(Request $request, string $role): RedirectResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $departmentId = $isSuperAdmin ? $request->input('department_id') : $user->department_id;
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'department_id' => [$isSuperAdmin ? 'required' : 'prohibited', 'integer', 'exists:departments,id'],
            'faculty' => ['nullable', 'string', 'max:255'],
        ];

        if ($role === 'Student') {
            $rules += [
                'student_number' => ['required', 'string', 'max:50', 'unique:users,student_number'],
                'academic_program_id' => ['required', 'integer', Rule::exists('academic_programs', 'id')->where('department_id', $departmentId)],
                'academic_level_id' => ['required', 'integer', Rule::exists('academic_levels', 'id')->where('department_id', $departmentId)],
            ];
        }

        $data = $request->validate($rules);

        abort_if(! $departmentId, 422, 'Select a department for this account.');

        if ($role === 'Student') {
            $program = AcademicProgram::findOrFail($data['academic_program_id']);
            if (! $program->levels()->whereKey($data['academic_level_id'])->exists()) {
                return back()->withErrors(['academic_level_id' => 'The selected level is not offered by this program.'])->withInput();
            }
            $data['program'] = $program->name;
            $data['academic_level'] = AcademicLevel::findOrFail($data['academic_level_id'])->name;
        }
        $data['account_status'] = 'active';

        $account = User::create([
            ...$data,
            'department_id' => $departmentId,
        ]);
        $account->assignRole($role);
        app(ActivityLogger::class)->record(strtolower($role).'.created', $account, ['department_id' => $departmentId]);

        return back()->with('status', $role.' account created.');
    }

    private function validateUserProfile(Request $request, int $departmentId, bool $isStudent): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->route('student')?->id)],
            'account_status' => ['required', Rule::in(['active', 'deactivated', 'suspended'])],
        ];

        if ($isStudent) {
            $rules += [
                'student_number' => ['required', 'string', 'max:50', Rule::unique('users', 'student_number')->ignore($request->route('student')?->id)],
                'academic_program_id' => ['required', 'integer', Rule::exists('academic_programs', 'id')->where('department_id', $departmentId)],
                'academic_level_id' => ['required', 'integer', Rule::exists('academic_levels', 'id')->where('department_id', $departmentId)],
            ];
        }

        $data = $request->validate($rules);

        if ($isStudent) {
            $program = AcademicProgram::where('department_id', $departmentId)->findOrFail($data['academic_program_id']);
            if (! $program->levels()->whereKey($data['academic_level_id'])->exists()) {
                return back()->withErrors(['academic_level_id' => 'The selected level is not offered by this program.'])->throwResponse();
            }
            $data['program'] = $program->name;
            $data['academic_level'] = AcademicLevel::where('department_id', $departmentId)->findOrFail($data['academic_level_id'])->name;
        }

        return $data;
    }

    private function usersForRole(string $role)
    {
        $user = auth()->user();

        return User::role($role)
            ->when(! $user->hasRole('Super Admin'), fn ($query) => $query->where('department_id', $user->department_id));
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

        return Course::query()
            ->when(! $user->hasRole('Super Admin'), fn ($query) => $query->where('department_id', $user->department_id));
    }

    private function registrationsForDepartment()
    {
        $user = auth()->user();

        return CourseRegistration::with(['student', 'course.department'])
            ->when(! $user->hasRole('Super Admin'), fn ($query) => $query->whereHas(
                'course', fn ($courses) => $courses->where('department_id', $user->department_id),
            ));
    }

    private function resultsForDepartment()
    {
        $user = auth()->user();

        return Result::with(['registration.student', 'registration.course.department', 'submitter'])
            ->whereHas('registration.course', fn ($query) => $query->when(
                ! $user->hasRole('Super Admin'), fn ($courses) => $courses->where('department_id', $user->department_id),
            ));
    }
}
