<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Result;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepartmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $this->admin = User::factory()->create(['department_id' => $this->department->id]);
        $role = Role::findOrCreate('Department Admin', 'web');
        $role->syncPermissions([
            Permission::findOrCreate('review registrations', 'web'),
            Permission::findOrCreate('review results', 'web'),
        ]);
        $this->admin->assignRole($role);
    }

    public function test_admin_can_confirm_a_pending_registration_only_once(): void
    {
        $registration = $this->registrationIn($this->department, 'Student One');

        $this->actingAs($this->admin)
            ->patch("/admin/registrations/{$registration->id}", ['status' => 'confirmed'])
            ->assertRedirect();

        $this->assertDatabaseHas('course_registrations', [
            'id' => $registration->id,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('system_activities', [
            'actor_id' => $this->admin->id,
            'action' => 'course_registration.confirmed',
            'subject_id' => $registration->id,
        ]);

        $this->patch("/admin/registrations/{$registration->id}", ['status' => 'rejected'])
            ->assertStatus(409);
    }

    public function test_admin_cannot_decide_a_registration_from_another_department(): void
    {
        $otherDepartment = Department::create(['name' => 'Business', 'code' => 'BUS']);
        $registration = $this->registrationIn($otherDepartment, 'Student Two');

        $this->actingAs($this->admin)
            ->patch("/admin/registrations/{$registration->id}", ['status' => 'confirmed'])
            ->assertNotFound();

        $this->assertDatabaseHas('course_registrations', [
            'id' => $registration->id,
            'status' => 'pending',
        ]);
    }

    public function test_returning_a_result_requires_a_note_and_records_the_reviewer(): void
    {
        $result = $this->submittedResultIn($this->department, 'Student Three');

        $this->actingAs($this->admin)
            ->patch("/admin/results/{$result->id}", ['status' => 'returned'])
            ->assertSessionHasErrors('review_note');

        $this->patch("/admin/results/{$result->id}", [
            'status' => 'returned',
            'review_note' => 'Please verify the examination score.',
        ])->assertRedirect();

        $this->assertDatabaseHas('results', [
            'id' => $result->id,
            'status' => 'returned',
            'reviewed_by' => $this->admin->id,
            'review_note' => 'Please verify the examination score.',
        ]);
        $this->assertDatabaseHas('result_events', [
            'result_id' => $result->id,
            'actor_id' => $this->admin->id,
            'event' => 'returned',
        ]);
        $this->assertDatabaseHas('system_activities', [
            'actor_id' => $this->admin->id,
            'action' => 'result.returned',
            'subject_id' => $result->id,
        ]);
    }

    public function test_admin_cannot_review_a_result_from_another_department(): void
    {
        $otherDepartment = Department::create(['name' => 'Business', 'code' => 'BUS']);
        $result = $this->submittedResultIn($otherDepartment, 'Student Four');

        $this->actingAs($this->admin)
            ->patch("/admin/results/{$result->id}", ['status' => 'approved'])
            ->assertNotFound();

        $this->assertDatabaseHas('results', ['id' => $result->id, 'status' => 'submitted']);
    }

    public function test_submitter_cannot_approve_their_own_result(): void
    {
        $result = $this->submittedResultIn($this->department, 'Student Five');
        $result->update(['submitted_by' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->patch("/admin/results/{$result->id}", ['status' => 'approved'])
            ->assertForbidden();

        $this->assertDatabaseHas('results', ['id' => $result->id, 'status' => 'submitted']);
    }

    private function registrationIn(Department $department, string $studentName): CourseRegistration
    {
        $student = User::factory()->create([
            'name' => $studentName,
            'department_id' => $department->id,
        ]);
        $course = Course::create([
            'department_id' => $department->id,
            'code' => 'CSE'.fake()->unique()->numerify('###'),
            'title' => 'Foundations of Computing',
            'credit_units' => 3,
        ]);

        return CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_year' => '2026/2027',
            'semester' => 'First',
            'status' => 'pending',
        ]);
    }

    private function submittedResultIn(Department $department, string $studentName): Result
    {
        $registration = $this->registrationIn($department, $studentName);
        $registration->update(['status' => 'confirmed']);

        return Result::create([
            'course_registration_id' => $registration->id,
            'ca_score' => 25,
            'exam_score' => 55,
            'total_score' => 80,
            'grade' => 'A',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }
}
