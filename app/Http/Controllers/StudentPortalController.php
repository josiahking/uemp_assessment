<?php

namespace App\Http\Controllers;

use App\Models\CourseOffering;
use App\Models\CourseRegistration;
use App\Services\AcademicCalculator;
use App\Services\ActivityLogger;
use App\Services\StudentCourseOfferingAvailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    public function __construct(
        private readonly AcademicCalculator $calculator,
        private readonly StudentCourseOfferingAvailability $offeringAvailability,
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
        $course = $offering->course;
        $academicYear = $offering->semester->academicSession->name;
        $semesterName = $offering->semester->name;

        $alreadyRegistered = CourseRegistration::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('academic_year', $academicYear)
            ->where('semester', $semesterName)
            ->exists();

        if ($alreadyRegistered) {
            return back()->withErrors(['course_id' => 'You already registered for this course in that semester.']);
        }

        $registration = CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'course_offering_id' => $offering->id,
            'academic_year' => $academicYear,
            'semester' => $semesterName,
            'status' => 'pending',
        ]);
        app(ActivityLogger::class)->record('course_registration.submitted', $registration, ['course_offering_id' => $offering->id]);

        return back()->with('status', 'Course registration submitted for review.');
    }
}
