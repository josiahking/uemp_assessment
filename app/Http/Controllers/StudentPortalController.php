<?php

namespace App\Http\Controllers;

use App\Models\CourseOffering;
use App\Services\AcademicCalculator;
use App\Services\StudentCourseOfferingAvailability;
use App\Services\StudentCourseRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    public function __construct(
        private readonly AcademicCalculator $calculator,
        private readonly StudentCourseOfferingAvailability $offeringAvailability,
        private readonly StudentCourseRegistration $courseRegistration,
    ) {}

    public function courses(): View
    {
        $student = auth()->user();
        $offerings = $this->offeringAvailability->for($student)
            ->with(['course.lecturers', 'course.department', 'semester.academicSession', 'program', 'academicLevel'])
            ->get();

        return view('student.courses', [
            'offerings' => $offerings,
            'registrations' => $student->courseRegistrations()->with(['course', 'result'])->latest()->get(),
            'gpa' => $this->calculator->cumulativeGpa($student),
        ]);
    }

    public function register(CourseOffering $offering): RedirectResponse
    {
        $student = auth()->user();
        $offering = $this->offeringAvailability->for($student)
            ->with(['course', 'semester.academicSession'])
            ->findOrFail($offering->id);
        if (! $this->courseRegistration->register($student, $offering)) {
            return back()->withErrors(['course_id' => 'You already registered for this course in that semester.']);
        }

        return back()->with('status', 'Course registration submitted for review.');
    }
}
