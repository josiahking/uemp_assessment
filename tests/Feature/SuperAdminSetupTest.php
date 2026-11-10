<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_departments_and_department_admin_accounts(): void
    {
        $this->withoutVite()->seed();
        $superAdmin = User::where('email', 'superadmin@uemp.test')->firstOrFail();

        $this->actingAs($superAdmin)->get('/super-admin/departments')->assertOk();

        $this->post('/super-admin/departments', [
            'name' => 'Health Sciences',
            'code' => 'HLTH',
        ])->assertRedirect();

        $department = Department::where('code', 'HLTH')->firstOrFail();

        $this->post('/super-admin/administrators', [
            'name' => 'Health Department Admin',
            'email' => 'health.admin@uemp.test',
            'password' => 'secure-password',
            'department_id' => $department->id,
        ])->assertRedirect();

        $admin = User::where('email', 'health.admin@uemp.test')->firstOrFail();
        $this->assertSame($department->id, $admin->department_id);
        $this->assertTrue($admin->hasRole('Department Admin'));
    }

    public function test_department_admin_cannot_manage_university_structure(): void
    {
        $this->seed();
        $departmentAdmin = User::where('email', 'admin@uemp.test')->firstOrFail();

        $this->actingAs($departmentAdmin)->get('/super-admin/departments')->assertForbidden();
    }
}