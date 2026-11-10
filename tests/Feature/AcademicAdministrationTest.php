<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_manage_faculties_programs_sessions_and_semesters(): void
    {
        $this->withoutVite()->seed();
        $superAdmin = User::where('email', 'superadmin@uemp.test')->firstOrFail();

        $this->actingAs($superAdmin)->get('/super-admin')->assertOk();
        $this->actingAs($superAdmin)->get('/super-admin/academics')->assertOk();

        $this->post('/super-admin/faculties', ['name' => 'Faculty of Science', 'code' => 'SCI'])
            ->assertRedirect();
        $facultyId = $this->app['db']->table('faculties')->where('code', 'SCI')->value('id');

        $this->patch('/super-admin/departments/'.$this->app['db']->table('departments')->where('code', 'TECH')->value('id'), [
            'name' => 'Technology & Engineering',
            'code' => 'TECH',
            'faculty_id' => $facultyId,
        ])->assertRedirect();

        $department = Department::where('code', 'TECH')->firstOrFail();
        $levelId = $this->app['db']->table('academic_levels')->where('department_id', $department->id)->value('id');
        $this->post('/super-admin/programs', [
            'department_id' => $department->id,
            'name' => 'Software Engineering',
            'code' => 'BSE-TEST',
            'academic_level_ids' => [$levelId],
        ])->assertRedirect();
        $programId = $this->app['db']->table('academic_programs')->where('code', 'BSE-TEST')->value('id');
        $this->assertDatabaseHas('academic_program_levels', ['academic_program_id' => $programId, 'academic_level_id' => $levelId]);

        $sessionId = $this->app['db']->table('academic_sessions')->where('name', '2027/2028')->value('id');

        $this->post('/super-admin/academic-sessions/'.$sessionId.'/semesters', [
            'name' => 'Second',
            'starts_at' => '2028-01-10',
            'ends_at' => '2028-05-30',
        ])->assertRedirect();

        $this->assertDatabaseHas('semesters', ['academic_session_id' => $sessionId, 'name' => 'Second']);
    }

    public function test_super_admin_can_suspend_accounts_and_suspended_users_cannot_sign_in(): void
    {
        $this->withoutVite()->seed();
        $superAdmin = User::where('email', 'superadmin@uemp.test')->firstOrFail();
        $student = User::where('email', 'student@uemp.test')->firstOrFail();

        $this->actingAs($superAdmin)->get('/super-admin/accounts')->assertOk();
        $this->patch('/super-admin/accounts/'.$student->id, [
            'role' => 'Student',
            'account_status' => 'suspended',
            'department_id' => $student->department_id,
            'student_number' => $student->student_number,
            'academic_program_id' => $student->academic_program_id,
            'academic_level_id' => $student->academic_level_id,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $student->id, 'account_status' => 'suspended']);
        Auth::logout();

        $this->post('/login', ['email' => $student->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_super_admin_can_manage_role_permissions(): void
    {
        $this->withoutVite()->seed();
        $superAdmin = User::where('email', 'superadmin@uemp.test')->firstOrFail();
        $lecturerRole = Role::findByName('Lecturer', 'web');

        $this->actingAs($superAdmin)->get('/super-admin/roles')->assertOk();
        $this->put('/super-admin/roles/'.$lecturerRole->id.'/permissions', [
            'permissions' => ['view assigned courses'],
        ])->assertRedirect();

        $this->assertDatabaseHas('role_has_permissions', [
            'role_id' => $lecturerRole->id,
            'permission_id' => Permission::findByName('view assigned courses', 'web')->id,
        ]);
        $this->assertDatabaseMissing('role_has_permissions', [
            'role_id' => $lecturerRole->id,
            'permission_id' => Permission::findByName('submit results', 'web')->id,
        ]);
    }

    public function test_super_admin_sign_in_lands_on_university_dashboard(): void
    {
        $this->withoutVite()->seed();

        $this->post('/login', [
            'email' => 'superadmin@uemp.test',
            'password' => 'password',
        ])->assertRedirect('/super-admin');

        $this->get('/dashboard')->assertRedirect('/super-admin');
    }

    public function test_activating_a_session_deactivates_the_previous_university_session(): void
    {
        $this->withoutVite()->seed();
        $superAdmin = User::where('email', 'superadmin@uemp.test')->firstOrFail();
        $previousSessionId = $this->app['db']->table('academic_sessions')->where('active', true)->value('id');

        $this->actingAs($superAdmin)->post('/super-admin/academic-sessions', [
            'name' => '2028/2029',
            'starts_at' => '2028-09-01',
            'ends_at' => '2029-07-31',
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('academic_sessions', ['id' => $previousSessionId, 'active' => false]);
        $this->assertDatabaseHas('academic_sessions', ['name' => '2028/2029', 'active' => true]);
        $this->assertSame(1, $this->app['db']->table('academic_sessions')->where('active', true)->count());
    }

    public function test_updating_a_session_to_active_deactivates_the_previous_session(): void
    {
        $this->withoutVite()->seed();
        $superAdmin = User::where('email', 'superadmin@uemp.test')->firstOrFail();
        $previousSessionId = $this->app['db']->table('academic_sessions')->where('active', true)->value('id');
        $newSessionId = $this->app['db']->table('academic_sessions')->insertGetId([
            'name' => '2028/2029',
            'starts_at' => '2028-09-01',
            'ends_at' => '2029-07-31',
            'active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($superAdmin)->patch('/super-admin/academic-sessions/'.$newSessionId, [
            'name' => '2028/2029',
            'starts_at' => '2028-09-01',
            'ends_at' => '2029-07-31',
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('academic_sessions', ['id' => $previousSessionId, 'active' => false]);
        $this->assertDatabaseHas('academic_sessions', ['id' => $newSessionId, 'active' => true]);
    }
}
