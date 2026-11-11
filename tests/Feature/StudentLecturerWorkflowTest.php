<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Result;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentLecturerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_only_for_a_matching_active_course_offering(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $level = AcademicLevel::create(['department_id' => $department->id, 'name' => '200', 'sort_order' => 200]);
        $program = AcademicProgram::create(['department_id' => $department->id, 'name' => 'Software Engineering', 'code' => 'BSE']);
        $program->levels()->attach($level);
        $student = User::factory()->create([
            'department_id' => $department->id,
            'academic_program_id' => $program->id,
            'academic_level_id' => $level->id,
        ]);
        $studentRole = Role::findOrCreate('Student', 'web');
        $studentRole->givePermissionTo([
            Permission::findOrCreate('register courses', 'web'),
            Permission::findOrCreate('view own records', 'web'),
        ]);
        $student->assignRole($studentRole);
        $course = $this->courseIn($department);
        $session = AcademicSession::create([
            'name' => '2027/2028',
            'starts_at' => '2027-09-01',
            'ends_at' => '2028-07-31',
            'active' => true,
        ]);
        $semester = Semester::create([
            'academic_session_id' => $session->id,
            'name' => 'First',
            'starts_at' => '2027-09-01',
            'ends_at' => '2027-12-20',
            'active' => true,
        ]);
        $eligibleOffering = CourseOffering::create([
            'course_id' => $course->id,
            'semester_id' => $semester->id,
            'academic_program_id' => $program->id,
            'academic_level_id' => $level->id,
            'active' => true,
        ]);
        $ineligibleOffering = CourseOffering::create([
            'course_id' => $course->id,
            'semester_id' => $semester->id,
            'academic_program_id' => null,
            'academic_level_id' => null,
            'active' => false,
        ]);

        $this->actingAs($student)->get('/student/courses')->assertOk()->assertSee($course->code);
        $this->post("/student/offerings/{$eligibleOffering->id}/registrations")->assertRedirect();
        $this->assertDatabaseHas('course_registrations', [
            'student_id' => $student->id,
            'course_offering_id' => $eligibleOffering->id,
            'academic_year' => '2027/2028',
            'semester' => 'First',
        ]);

        $this->post("/student/offerings/{$ineligibleOffering->id}/registrations")->assertNotFound();

        $program->update(['active' => false]);
        $this->post("/student/offerings/{$eligibleOffering->id}/registrations")->assertNotFound();
    }

    public function test_student_can_register_only_for_an_active_course_in_their_department(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $otherDepartment = Department::create(['name' => 'Business', 'code' => 'BUS']);
        $student = User::factory()->create(['department_id' => $department->id]);
        $studentRole = Role::findOrCreate('Student', 'web');
        $studentRole->givePermissionTo(Permission::findOrCreate('register courses', 'web'));
        $student->assignRole($studentRole);
        $course = $this->courseIn($department);
        $otherCourse = $this->courseIn($otherDepartment);
        $session = AcademicSession::create([
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-07-31',
            'active' => true,
        ]);
        $semester = Semester::create([
            'academic_session_id' => $session->id,
            'name' => 'First',
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-12-20',
            'active' => true,
        ]);
        $offering = CourseOffering::create([
            'course_id' => $course->id,
            'semester_id' => $semester->id,
            'active' => true,
        ]);
        $otherOffering = CourseOffering::create([
            'course_id' => $otherCourse->id,
            'semester_id' => $semester->id,
            'active' => true,
        ]);

        $this->actingAs($student)->post("/student/offerings/{$offering->id}/registrations")->assertRedirect();

        $this->assertDatabaseHas('course_registrations', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'pending',
        ]);

        $this->post("/student/offerings/{$offering->id}/registrations")->assertSessionHasErrors('course_id');

        $this->post("/student/offerings/{$otherOffering->id}/registrations")->assertNotFound();
    }

    public function test_assigned_lecturer_can_submit_scores_with_computed_grade(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $lecturer = User::factory()->create(['department_id' => $department->id]);
        $lecturerRole = Role::findOrCreate('Lecturer', 'web');
        $lecturerRole->givePermissionTo(Permission::findOrCreate('submit results', 'web'));
        $lecturer->assignRole($lecturerRole);
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
        $resultId = Result::where('course_registration_id', $registration->id)->value('id');
        $this->assertDatabaseHas('system_activities', [
            'actor_id' => $lecturer->id,
            'action' => 'result.submitted',
            'subject_id' => $resultId,
        ]);

        $this->post("/lecturer/registrations/{$registration->id}/result", [
            'ca_score' => 30,
            'exam_score' => 50,
        ])->assertStatus(409);

        $this->assertSame(1, Result::where('course_registration_id', $registration->id)->count());
    }

    public function test_lecturer_cannot_submit_results_for_a_course_not_assigned_to_them(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $lecturer = User::factory()->create(['department_id' => $department->id]);
        $lecturerRole = Role::findOrCreate('Lecturer', 'web');
        $lecturerRole->givePermissionTo(Permission::findOrCreate('submit results', 'web'));
        $lecturer->assignRole($lecturerRole);
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
