<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_accounts_and_academic_records_support_the_admin_workflow(): void
    {
        $this->seed();

        $this->assertDatabaseHas('roles', ['name' => 'Department Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Super Admin']);
        $this->assertDatabaseHas('users', ['email' => 'admin@uemp.test']);
        $this->assertDatabaseHas('users', ['email' => 'lecturer@uemp.test']);
        $this->assertDatabaseHas('users', ['email' => 'student@uemp.test']);
        $student = User::where('email', 'student@uemp.test')->firstOrFail();

        $this->assertDatabaseHas('course_registrations', [
            'student_id' => $student->id,
            'status' => 'pending',
        ]);
        $this->assertTrue(User::where('email', 'admin@uemp.test')->firstOrFail()->hasPermissionTo('manage students'));
    }
}
