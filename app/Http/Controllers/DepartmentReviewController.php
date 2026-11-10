<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Result;
use App\Models\ResultEvent;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentReviewController extends Controller
{
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