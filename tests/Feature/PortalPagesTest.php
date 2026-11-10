<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_admin_pages_render_with_seeded_data(): void
    {
        $this->withoutVite()->seed();
        $admin = User::where('email', 'admin@uemp.test')->firstOrFail();

        foreach (['/dashboard', '/admin/students', '/admin/lecturers', '/admin/courses', '/admin/registrations', '/admin/results', '/admin/reports'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }

    public function test_student_and_lecturer_pages_render_for_their_roles(): void
    {
        $this->withoutVite()->seed();
        $student = User::where('email', 'student@uemp.test')->firstOrFail();
        $lecturer = User::where('email', 'lecturer@uemp.test')->firstOrFail();

        $this->actingAs($student)->get('/dashboard')->assertOk()->assertSee('Course registrations');
        $this->actingAs($student)->get('/student/courses')->assertOk()->assertSee('SWE201');
        $this->actingAs($lecturer)->get('/dashboard')->assertOk()->assertSee('Assigned courses');
        $this->actingAs($lecturer)->get('/lecturer/courses')->assertOk()->assertSee('SWE201');
    }
}