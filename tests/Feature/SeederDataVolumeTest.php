<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Result;
use App\Models\ResultEvent;
use App\Models\Semester;
use App\Models\SystemActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederDataVolumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_data_is_capped_at_twenty_and_covers_workflow_scenarios(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(20, User::role('Student')->count());
        $this->assertSame(20, User::role('Lecturer')->count());

        foreach ([
            Faculty::class,
            Department::class,
            AcademicProgram::class,
            AcademicLevel::class,
            AcademicSession::class,
            Semester::class,
            Course::class,
            CourseOffering::class,
            CourseRegistration::class,
            Result::class,
            ResultEvent::class,
            SystemActivity::class,
        ] as $model) {
            $this->assertLessThanOrEqual(20, $model::count(), "{$model} seed count exceeded 20.");
        }

        $this->assertSame(20, User::role('Student')->whereNotNull('student_number')->distinct('student_number')->count('student_number'));
        $this->assertContains('pending', CourseRegistration::query()->distinct()->pluck('status')->all());
        $this->assertContains('confirmed', CourseRegistration::query()->distinct()->pluck('status')->all());
        $this->assertContains('rejected', CourseRegistration::query()->distinct()->pluck('status')->all());
        $this->assertContains('submitted', Result::query()->distinct()->pluck('status')->all());
        $this->assertContains('approved', Result::query()->distinct()->pluck('status')->all());
        $this->assertContains('returned', Result::query()->distinct()->pluck('status')->all());
        $this->assertContains('rejected', Result::query()->distinct()->pluck('status')->all());
    }
}
