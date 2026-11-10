<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Result;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentLecturerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_only_for_an_active_course_in_their_department(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $otherDepartment = Department::create(['name' => 'Business', 'code' => 'BUS']);
        $student = User::factory()->create(['department_id' => $department->id]);
        $student->assignRole(Role::findOrCreate('Student', 'web'));
        $course = $this->courseIn($department);
        $otherCourse = $this->courseIn($otherDepartment);

        $this->actingAs($student)->post("/student/courses/{$course->id}/registrations", [
            'academic_year' => '2026/2027',
            'semester' => 'First',
        ])->assertRedirect();

        $this->assertDatabaseHas('course_registrations', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'pending',
        ]);

        $this->post("/student/courses/{$course->id}/registrations", [
            'academic_year' => '2026/2027',
            'semester' => 'First',
        ])->assertSessionHasErrors('course_id');

        $this->post("/student/courses/{$otherCourse->id}/registrations", [
            'academic_year' => '2026/2027',
            'semester' => 'First',
        ])->assertNotFound();
    }

    public function test_assigned_lecturer_can_submit_scores_with_computed_grade(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $lecturer = User::factory()->create(['department_id' => $department->id]);
        $lecturer->assignRole(Role::findOrCreate('Lecturer', 'web'));
        $student = User::factory()->create(['department_id' => $department->id]);
        $course = $this->courseIn($department);
        $course->lecturers()->attach($lecturer);
        $registration = CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_year' => '2026/2027',
            'semester' => 'First',
            'status' => 'confirmed',
        ]);

        $this->actingAs($lecturer)
            ->post("/lecturer/registrations/{$registration->id}/result", [
                'ca_score' => 41,
                'exam_score' => 20,
            ])
            ->assertSessionHasErrors('ca_score');

        $this->actingAs($lecturer)
            ->post("/lecturer/registrations/{$registration->id}/result", [
                'ca_score' => 26,
                'exam_score' => 45,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('results', [
            'course_registration_id' => $registration->id,
            'submitted_by' => $lecturer->id,
            'total_score' => 71,
            'grade' => 'B',
            'status' => 'submitted',
        ]);

        $this->assertSame(1, Result::where('course_registration_id', $registration->id)->count());
    }

    public function test_lecturer_cannot_submit_results_for_a_course_not_assigned_to_them(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $lecturer = User::factory()->create(['department_id' => $department->id]);
        $lecturer->assignRole(Role::findOrCreate('Lecturer', 'web'));
        $student = User::factory()->create(['department_id' => $department->id]);
        $course = $this->courseIn($department);
        $registration = CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_year' => '2026/2027',
            'semester' => 'First',
            'status' => 'confirmed',
        ]);

        $this->actingAs($lecturer)
            ->post("/lecturer/registrations/{$registration->id}/result", [
                'ca_score' => 20,
                'exam_score' => 30,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('results', 0);
    }

    private function courseIn(Department $department): Course
    {
        return Course::create([
            'department_id' => $department->id,
            'code' => 'CSE'.fake()->unique()->numerify('###'),
            'title' => 'Foundations of Computing',
            'credit_units' => 3,
            'active' => true,
        ]);
    }
}
