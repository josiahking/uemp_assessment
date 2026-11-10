<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Result;
use App\Models\ResultEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        return $this->storeUser($request, 'Student');
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

    public function courses(): View
    {
        $user = auth()->user();

        return view('admin.courses.index', [
            'courses' => $this->coursesForDepartment()->with(['department', 'lecturers'])->orderBy('code')->get(),
            'lecturers' => $this->usersForRole('Lecturer')->orderBy('name')->get(),
            'departments' => $user->hasRole('Super Admin') ? Department::orderBy('name')->get() : collect(),
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

        Course::create([
            'department_id' => $departmentId,
            'code' => $data['code'],
            'title' => $data['title'],
            'credit_units' => $data['credit_units'],
            'active' => true,
        ]);

        return back()->with('status', 'Course created.');
    }

    public function assignLecturer(Request $request, Course $course): RedirectResponse
    {
        $course = $this->coursesForDepartment()->findOrFail($course->id);
        $data = $request->validate(['lecturer_id' => ['required', 'integer', 'exists:users,id']]);
        $lecturer = User::role('Lecturer')
            ->where('department_id', $course->department_id)
            ->findOrFail($data['lecturer_id']);

        $course->lecturers()->syncWithoutDetaching([$lecturer->id]);

        return back()->with('status', 'Lecturer assigned to course.');
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
        $registration->update(['status' => $data['status']]);

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

        return view('admin.reports.index', [
            'students' => $students,
            'lecturers' => $lecturers,
            'courses' => $courses,
            'pendingRegistrations' => (clone $registrations)->where('status', 'pending')->count(),
            'approvedResults' => (clone $results)->where('status', 'approved')->count(),
            'departmentName' => $departmentId ? $user->department?->name : 'All departments',
        ]);
    }

    private function storeUser(Request $request, string $role): RedirectResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
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
                'program' => ['required', 'string', 'max:255'],
                'academic_level' => ['required', 'string', 'max:50'],
            ];
        }

        $data = $request->validate($rules);
        $departmentId = $isSuperAdmin ? $data['department_id'] : $user->department_id;

        abort_if(! $departmentId, 422, 'Select a department for this account.');

        $account = User::create([
            ...$data,
            'department_id' => $departmentId,
        ]);
        $account->assignRole($role);

        return back()->with('status', $role.' account created.');
    }

    private function usersForRole(string $role)
    {
        $user = auth()->user();

        return User::role($role)
            ->when(! $user->hasRole('Super Admin'), fn ($query) => $query->where('department_id', $user->department_id));
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
