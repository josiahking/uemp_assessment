<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $admin->assignRole(Role::findOrCreate('Department Admin', 'web'));

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
}
