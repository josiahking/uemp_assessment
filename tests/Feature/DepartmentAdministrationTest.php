<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepartmentAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_department_admin_only_sees_students_in_their_department(): void
    {
        $technology = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $business = Department::create(['name' => 'Business', 'code' => 'BUS']);
        $admin = User::factory()->create(['department_id' => $technology->id]);
        $role = Role::findOrCreate('Department Admin', 'web');
        $role->givePermissionTo(Permission::findOrCreate('manage students', 'web'));
        $admin->assignRole($role);

        User::factory()->create([
            'name' => 'Technology Student',
            'email' => 'technology.student@example.test',
            'department_id' => $technology->id,
        ])->assignRole(Role::findOrCreate('Student', 'web'));

        User::factory()->create([
            'name' => 'Business Student',
            'email' => 'business.student@example.test',
            'department_id' => $business->id,
        ])->assignRole(Role::findOrCreate('Student', 'web'));

        $this->actingAs($admin)
            ->get('/admin/students')
            ->assertOk()
            ->assertSee('Technology Student')
            ->assertDontSee('Business Student');
    }

    public function test_student_cannot_access_department_admin_routes(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $student = User::factory()->create(['department_id' => $department->id]);
        $student->assignRole(Role::findOrCreate('Student', 'web'));

        $this->actingAs($student)->get('/admin/students')->assertForbidden();
    }

    public function test_department_admin_can_update_local_student_profile_but_not_foreign_student(): void
    {
        $technology = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $business = Department::create(['name' => 'Business', 'code' => 'BUS']);
        $program = AcademicProgram::create(['department_id' => $technology->id, 'name' => 'Software Engineering', 'code' => 'BSE']);
        $level = AcademicLevel::create(['department_id' => $technology->id, 'name' => '300', 'sort_order' => 300]);
        $program->levels()->attach($level);
        $admin = User::factory()->create(['department_id' => $technology->id]);
        $role = Role::findOrCreate('Department Admin', 'web');
        $role->givePermissionTo(Permission::findOrCreate('manage students', 'web'));
        $admin->assignRole($role);
        $localStudent = User::factory()->create(['department_id' => $technology->id]);
        $localStudent->assignRole(Role::findOrCreate('Student', 'web'));
        $foreignStudent = User::factory()->create(['department_id' => $business->id]);
        $foreignStudent->assignRole(Role::findOrCreate('Student', 'web'));

        $this->actingAs($admin)->patch("/admin/students/{$localStudent->id}", [
            'name' => 'Updated Student',
            'email' => $localStudent->email,
            'student_number' => 'TECH-300-001',
            'academic_program_id' => $program->id,
            'academic_level_id' => $level->id,
            'account_status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $localStudent->id,
            'name' => 'Updated Student',
            'academic_program_id' => $program->id,
            'academic_level_id' => $level->id,
        ]);

        $this->patch("/admin/students/{$foreignStudent->id}", [
            'name' => 'Unauthorized Update',
            'email' => $foreignStudent->email,
            'student_number' => 'BUS-001',
            'academic_program_id' => $program->id,
            'academic_level_id' => $level->id,
            'account_status' => 'active',
        ])->assertNotFound();
    }

    public function test_department_admin_without_student_permission_cannot_open_student_management(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole(Role::findOrCreate('Department Admin', 'web'));
        Role::findOrCreate('Student', 'web');
        Permission::findOrCreate('manage students', 'web');

        $this->actingAs($admin)->get('/admin/students')->assertForbidden();
    }
}
