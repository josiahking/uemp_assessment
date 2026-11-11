<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Result;
use App\Models\User;
use App\Services\DepartmentReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentReviewController extends Controller
{
    public function __construct(private readonly DepartmentReviewService $reviewService) {}

    public function registrations(Request $request): View
    {
        $query = $this->registrationsForDepartment();
        $pendingCount = (clone $query)->where('status', 'pending')->count();
        $rawStatus = $request->query('status');
        $status = is_string($rawStatus) ? $rawStatus : '';
        $rawAcademicYear = $request->query('academic_year', '');
        $academicYear = is_string($rawAcademicYear) ? mb_substr(trim($rawAcademicYear), 0, 9) : '';
        $rawSemester = $request->query('semester');
        $semester = is_string($rawSemester) ? $rawSemester : '';
        $rawDepartmentId = $request->query('department_id');
        $departmentId = $request->user()->hasRole('Super Admin') && is_string($rawDepartmentId) ? $rawDepartmentId : null;

        if (in_array($status, ['pending', 'confirmed', 'rejected'], true)) {
            $query->where('status', $status);
        }
        if ($academicYear !== '') {
            $query->where('academic_year', $academicYear);
        }
        if (in_array($semester, ['First', 'Second', 'Summer'], true)) {
            $query->where('semester', $semester);
        }
        if ($departmentId && $request->user()->hasRole('Super Admin')) {
            $query->whereHas('course', fn ($courses) => $courses->where('department_id', $departmentId));
        }
        $registrations = $query->with(['student.department', 'course'])->latest()->paginate(20)->withQueryString();

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'pendingCount' => $pendingCount,
            'filters' => compact('status', 'academicYear', 'semester', 'departmentId'),
            'departments' => $request->user()->hasRole('Super Admin') ? Department::orderBy('name')->get() : collect(),
        ]);
    }

    public function updateRegistration(Request $request, CourseRegistration $registration): RedirectResponse
    {
        $registration = $this->registrationsForDepartment()->findOrFail($registration->id);
        $data = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'rejected'])],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:2000'],
        ]);
        $this->reviewService->decideRegistration($registration, $data['status'], $data['rejection_reason'] ?? null);

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
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'returned'])],
            'review_note' => ['required_if:status,rejected,returned', 'nullable', 'string', 'max:2000'],
        ]);
        $this->reviewService->reviewResult($result, $request->user(), $data['status'], $data['review_note'] ?? null);

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

    private function usersForRole(string $role)
    {
        $user = auth()->user();

        return User::role($role)->when(
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

    private function registrationsForDepartment()
    {
        $user = auth()->user();

        return CourseRegistration::with(['student', 'course.department'])->when(
            ! $user->hasRole('Super Admin'), fn ($query) => $query->whereHas(
                'course', fn ($courses) => $courses->where('department_id', $user->department_id),
            ),
        );
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
