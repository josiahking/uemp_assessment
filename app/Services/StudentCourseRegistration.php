<?php

namespace App\Services;

use App\Models\CourseOffering;
use App\Models\CourseRegistration;
use App\Models\User;

class StudentCourseRegistration
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function register(User $student, CourseOffering $offering): bool
    {
        $course = $offering->course;
        $academicYear = $offering->semester->academicSession->name;
        $semester = $offering->semester->name;
        $alreadyRegistered = CourseRegistration::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->exists();
        if ($alreadyRegistered) {
            return false;
        }
        $registration = CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'course_offering_id' => $offering->id,
            'academic_year' => $academicYear,
            'semester' => $semester,
            'status' => 'pending',
        ]);
        $this->activityLogger->record('course_registration.submitted', $registration, ['course_offering_id' => $offering->id]);

        return true;
    }
}
