<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Result;
use App\Models\User;
use App\Services\AcademicCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AcademicCalculatorTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('gradeBoundaries')]
    public function test_scores_map_to_the_documented_grade_scale(float $score, string $grade, float $point): void
    {
        $calculator = new AcademicCalculator;

        $this->assertSame($grade, $calculator->gradeFor($score));
        $this->assertSame($point, $calculator->gradePointFor($grade));
    }

    public static function gradeBoundaries(): array
    {
        return [
            'A boundary' => [80, 'A', 4.0],
            'B boundary' => [70, 'B', 3.0],
            'C boundary' => [60, 'C', 2.0],
            'D boundary' => [50, 'D', 1.0],
            'F boundary' => [49.99, 'F', 0.0],
        ];
    }

    public function test_cumulative_gpa_uses_only_approved_results_and_course_credits(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $student = User::factory()->create(['department_id' => $department->id]);

        $this->createResult($department, $student, 3, 80, 'approved', 'A');
        $this->createResult($department, $student, 1, 60, 'approved', 'C');
        $this->createResult($department, $student, 5, 100, 'submitted', 'A');

        $this->assertSame(3.5, (new AcademicCalculator)->cumulativeGpa($student));
    }

    private function createResult(Department $department, User $student, int $credits, float $score, string $status, string $grade): void
    {
        $course = Course::create([
            'department_id' => $department->id,
            'code' => fake()->unique()->bothify('CSE###'),
            'title' => 'Academic course',
            'credit_units' => $credits,
        ]);
        $registration = CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_year' => '2026/2027',
            'semester' => 'First',
            'status' => 'confirmed',
        ]);

        Result::create([
            'course_registration_id' => $registration->id,
            'total_score' => $score,
            'grade' => $grade,
            'status' => $status,
        ]);
    }
}
