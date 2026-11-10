<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Result;
use App\Models\User;
use App\Services\AcademicCalculator;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly AcademicCalculator $calculator) {}

    public function __invoke(): View
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $departmentId = $user->department_id;

        if ($user->hasAnyRole(['Department Admin', 'Super Admin'])) {
            $students = User::role('Student')->when(! $isSuperAdmin, fn ($query) => $query->where('department_id', $departmentId))->count();
            $lecturers = User::role('Lecturer')->when(! $isSuperAdmin, fn ($query) => $query->where('department_id', $departmentId))->count();
            $courses = Course::when(! $isSuperAdmin, fn ($query) => $query->where('department_id', $departmentId))->count();
            $registrations = CourseRegistration::where('status', 'pending')
                ->whereHas('course', fn ($query) => $query->when(! $isSuperAdmin, fn ($courses) => $courses->where('department_id', $departmentId)))
                ->count();
            $results = Result::where('status', 'submitted')
                ->whereHas('registration.course', fn ($query) => $query->when(! $isSuperAdmin, fn ($courses) => $courses->where('department_id', $departmentId)))
                ->count();

            return view('dashboard', compact('students', 'lecturers', 'courses', 'registrations', 'results'));
        }

        if ($user->hasRole('Lecturer')) {
            $courses = $user->courses()->count();
            $registrations = CourseRegistration::where('status', 'confirmed')
                ->whereHas('course.lecturers', fn ($query) => $query->where('users.id', $user->id))
                ->count();
            $results = Result::where('submitted_by', $user->id)->whereIn('status', ['draft', 'returned'])->count();

            return view('dashboard', compact('courses', 'registrations', 'results'));
        }

        $registrations = $user->courseRegistrations()->count();
        $courses = Course::where('department_id', $user->department_id)->where('active', true)->count();
        $results = Result::where('status', 'approved')
            ->whereHas('registration', fn ($query) => $query->where('student_id', $user->id))
            ->count();
        $gpa = $this->calculator->cumulativeGpa($user);

        return view('dashboard', compact('courses', 'registrations', 'results', 'gpa'));
    }
}
