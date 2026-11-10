<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DepartmentCourseService
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function createCourse(array $data, int $departmentId): Course
    {
        $course = Course::create(['department_id' => $departmentId, 'code' => $data['code'], 'title' => $data['title'], 'credit_units' => $data['credit_units'], 'active' => true]);
        $this->activityLogger->record('course.created', $course);

        return $course;
    }

    public function updateCourse(Course $course, array $data): void
    {
        $course->update($data);
        $this->activityLogger->record('course.updated', $course, $data);
    }

    public function createOffering(Course $course, array $data): CourseOffering
    {
        if (($data['academic_program_id'] ?? null) && ($data['academic_level_id'] ?? null)) {
            $program = AcademicProgram::findOrFail($data['academic_program_id']);
            if (! $program->levels()->whereKey($data['academic_level_id'])->exists()) {
                throw ValidationException::withMessages(['academic_level_id' => 'The selected level is not offered by this program.']);
            }
        }
        $duplicate = CourseOffering::where('course_id', $course->id)
            ->where('semester_id', $data['semester_id'])
            ->where('academic_program_id', $data['academic_program_id'] ?? null)
            ->where('academic_level_id', $data['academic_level_id'] ?? null)
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['semester_id' => 'This course offering already exists.']);
        }
        $offering = CourseOffering::create(['course_id' => $course->id, ...$data, 'active' => true]);
        $this->activityLogger->record('course_offering.created', $offering, $data);

        return $offering;
    }

    public function updateOffering(CourseOffering $offering, array $data): void
    {
        $offering->update($data);
        $this->activityLogger->record('course_offering.updated', $offering, $data);
    }

    public function assignLecturer(Course $course, User $lecturer): void
    {
        $course->lecturers()->syncWithoutDetaching([$lecturer->id]);
        $this->activityLogger->record('lecturer.assigned_to_course', $course, ['lecturer_id' => $lecturer->id]);
    }

    public function unassignLecturer(Course $course, User $lecturer): void
    {
        $course->lecturers()->detach($lecturer->id);
        $this->activityLogger->record('lecturer.unassigned_from_course', $course, ['lecturer_id' => $lecturer->id]);
    }
}
