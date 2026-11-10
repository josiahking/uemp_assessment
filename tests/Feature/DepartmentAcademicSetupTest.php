<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Department;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepartmentAcademicSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_admin_manages_only_programs_and_levels_in_their_department(): void
    {
        $this->withoutVite();
        $technology = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $business = Department::create(['name' => 'Business', 'code' => 'BUS']);
        $admin = User::factory()->create(['department_id' => $technology->id]);
        $role = Role::findOrCreate('Department Admin', 'web');
        $role->givePermissionTo(Permission::findOrCreate('manage academic catalog', 'web'));
        $admin->assignRole($role);

        $this->actingAs($admin)->get('/admin/academics')->assertOk();
        $this->post('/admin/academic-levels', [
            'name' => '300',
            'sort_order' => 300,
        ])->assertRedirect();
        $levelId = $this->app['db']->table('academic_levels')->where('department_id', $technology->id)->value('id');

        $this->post('/admin/programs', [
            'name' => 'Software Engineering',
            'code' => 'BSE',
            'academic_level_ids' => [$levelId],
        ])->assertRedirect();

        $this->assertDatabaseHas('academic_programs', ['department_id' => $technology->id, 'code' => 'BSE']);
        $this->assertDatabaseHas('academic_program_levels', ['academic_level_id' => $levelId]);

        $foreignLevelId = $this->app['db']->table('academic_levels')->insertGetId([
            'department_id' => $business->id,
            'name' => '100',
            'sort_order' => 100,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/admin/programs', [
            'name' => 'Unauthorized Program',
            'code' => 'BAD',
            'academic_level_ids' => [$foreignLevelId],
        ])->assertSessionHasErrors('academic_level_ids.0');

        $this->get('/admin/academics')->assertDontSee('Business');
    }

    public function test_department_admin_can_allocate_only_local_courses_and_catalog_records(): void
    {
        $technology = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $business = Department::create(['name' => 'Business', 'code' => 'BUS']);
        $admin = User::factory()->create(['department_id' => $technology->id]);
        $role = Role::findOrCreate('Department Admin', 'web');
        $role->givePermissionTo(Permission::findOrCreate('manage courses', 'web'));
        $admin->assignRole($role);
        $course = Course::create(['department_id' => $technology->id, 'code' => 'SWE201', 'title' => 'Software Engineering', 'credit_units' => 3]);
        $foreignCourse = Course::create(['department_id' => $business->id, 'code' => 'BUS101', 'title' => 'Business Studies', 'credit_units' => 3]);
        $level = AcademicLevel::create(['department_id' => $technology->id, 'name' => '200', 'sort_order' => 200]);
        $program = AcademicProgram::create(['department_id' => $technology->id, 'name' => 'Software Engineering', 'code' => 'BSE']);
        $program->levels()->attach($level);
        $session = AcademicSession::create(['name' => '2026/2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-07-31', 'active' => true]);
        $semester = Semester::create(['academic_session_id' => $session->id, 'name' => 'First', 'starts_at' => '2026-09-01', 'ends_at' => '2026-12-20', 'active' => true]);

        $this->actingAs($admin)->post("/admin/courses/{$course->id}/offerings", [
            'semester_id' => $semester->id,
            'academic_program_id' => $program->id,
            'academic_level_id' => $level->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('course_offerings', [
            'course_id' => $course->id,
            'semester_id' => $semester->id,
            'academic_program_id' => $program->id,
            'academic_level_id' => $level->id,
        ]);

        $this->post("/admin/courses/{$foreignCourse->id}/offerings", [
            'semester_id' => $semester->id,
        ])->assertNotFound();
    }
}
