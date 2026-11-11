<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\SystemActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminListPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_student_directory_paginates_and_filters_server_side(): void
    {
        $this->withoutVite();
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $adminRole = Role::findOrCreate('Department Admin', 'web');
        $adminRole->givePermissionTo(Permission::findOrCreate('manage students', 'web'));
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole($adminRole);
        $studentRole = Role::findOrCreate('Student', 'web');

        foreach (range(1, 25) as $number) {
            User::factory()->create([
                'name' => "Roster Student {$number}",
                'email' => "roster.{$number}@example.test",
                'department_id' => $department->id,
                'account_status' => $number === 25 ? 'suspended' : 'active',
            ])->assignRole($studentRole);
        }

        $this->actingAs($admin)
            ->get('/admin/students')
            ->assertOk()
            ->assertViewHas('students', fn ($students) => $students->total() === 25 && $students->perPage() === 20);

        $this->get('/admin/students?q=roster.25&account_status=suspended')
            ->assertOk()
            ->assertViewHas('students', fn ($students) => $students->total() === 1 && $students->first()->email === 'roster.25@example.test');
    }

    public function test_super_admin_account_directory_excludes_students_and_filters_staff_by_role_status_and_text(): void
    {
        $this->withoutVite();
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $superAdmin = User::factory()->create(['department_id' => null]);
        $superAdmin->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $lecturerRole = Role::findOrCreate('Lecturer', 'web');
        $studentRole = Role::findOrCreate('Student', 'web');

        foreach (range(1, 25) as $number) {
            $lecturer = User::factory()->create([
                'name' => "Account Lecturer {$number}",
                'email' => "account.{$number}@example.test",
                'department_id' => $department->id,
                'account_status' => $number === 25 ? 'suspended' : 'active',
            ]);
            $lecturer->assignRole($lecturerRole);
        }
        $student = User::factory()->create([
            'name' => 'Account Student',
            'email' => 'account.student@example.test',
            'department_id' => $department->id,
        ]);
        $student->assignRole($studentRole);

        $this->actingAs($superAdmin)
            ->get('/super-admin/accounts?role=Lecturer&account_status=active')
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 24 && $users->perPage() === 20)
            ->assertViewHas('roles', fn ($roles) => ! $roles->contains('name', 'Student'));

        $this->get('/super-admin/accounts?role=Lecturer&account_status=suspended&q=account.25')
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->first()->email === 'account.25@example.test');

        $this->get('/super-admin/accounts?role=Student&q=account.student@example.test')
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 0);
    }

    public function test_registration_list_paginates_and_filters_by_status_and_session(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $adminRole = Role::findOrCreate('Department Admin', 'web');
        $adminRole->givePermissionTo(Permission::findOrCreate('review registrations', 'web'));
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole($adminRole);
        $studentRole = Role::findOrCreate('Student', 'web');
        $course = Course::create(['department_id' => $department->id, 'code' => 'GEN100', 'title' => 'General Studies', 'credit_units' => 3]);

        foreach (range(1, 25) as $number) {
            $student = User::factory()->create(['department_id' => $department->id]);
            $student->assignRole($studentRole);
            CourseRegistration::create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'academic_year' => $number < 25 ? '2026/2027' : '2025/2026',
                'semester' => 'First',
                'status' => $number === 25 ? 'confirmed' : 'pending',
            ]);
        }

        $this->actingAs($admin)
            ->get('/admin/registrations?status=pending&academic_year=2026%2F2027')
            ->assertOk()
            ->assertViewHas('registrations', fn ($registrations) => $registrations->total() === 24 && $registrations->perPage() === 20);
    }

    public function test_super_admin_activity_history_paginates_and_filters(): void
    {
        $this->withoutVite();
        $superAdmin = User::factory()->create(['department_id' => null]);
        $superAdmin->assignRole(Role::findOrCreate('Super Admin', 'web'));

        foreach (range(1, 25) as $number) {
            SystemActivity::create([
                'actor_id' => $superAdmin->id,
                'action' => $number === 25 ? 'result.approved' : 'course.created',
                'subject_type' => Course::class,
                'subject_id' => $number,
                'details' => ['index' => $number],
                'created_at' => now()->subMinutes($number),
            ]);
        }

        $this->actingAs($superAdmin)
            ->get('/super-admin/activity')
            ->assertOk()
            ->assertViewHas('activities', fn ($activities) => $activities->total() === 25 && $activities->perPage() === 20);

        $this->get('/super-admin/activity?action=result.approved')
            ->assertOk()
            ->assertViewHas('activities', fn ($activities) => $activities->total() === 1 && $activities->first()->action === 'result.approved');
    }
}
