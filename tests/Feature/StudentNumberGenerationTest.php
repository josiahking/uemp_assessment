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

class StudentNumberGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_admin_student_creation_generates_incrementing_numbers(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $level = AcademicLevel::create(['department_id' => $department->id, 'name' => '100', 'sort_order' => 100]);
        $program = AcademicProgram::create(['department_id' => $department->id, 'name' => 'Computing', 'code' => 'BCS']);
        $program->levels()->attach($level);
        $role = Role::findOrCreate('Department Admin', 'web');
        $role->givePermissionTo(Permission::findOrCreate('manage students', 'web'));
        Role::findOrCreate('Student', 'web');
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole($role);

        foreach (['first', 'second'] as $suffix) {
            $this->actingAs($admin)->post('/admin/students', [
                'name' => "Student {$suffix}",
                'email' => "{$suffix}.student@example.test",
                'password' => 'secure-password',
                'academic_program_id' => $program->id,
                'academic_level_id' => $level->id,
                'faculty' => 'Faculty of Technology',
            ])->assertRedirect()->assertSessionHasNoErrors();
        }

        $numbers = User::whereIn('email', ['first.student@example.test', 'second.student@example.test'])
            ->orderBy('student_number')
            ->pluck('student_number');

        $this->assertCount(2, $numbers);
        $this->assertMatchesRegularExpression('/^UEMP-\d{4}-\d{6}$/', $numbers[0]);
        $this->assertMatchesRegularExpression('/^UEMP-\d{4}-\d{6}$/', $numbers[1]);
        $this->assertSame((int) substr($numbers[0], -6) + 1, (int) substr($numbers[1], -6));
    }
}
