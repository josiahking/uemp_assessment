<?php

namespace App\Services;

use App\Models\Result;
use App\Models\User;

class AcademicCalculator
{
    public function gradeFor(float $score): string
    {
        return match (true) {
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default => 'F',
        };
    }

    public function gradePointFor(string $grade): float
    {
        return match (strtoupper($grade)) {
            'A' => 4.0,
            'B' => 3.0,
            'C' => 2.0,
            'D' => 1.0,
            'F' => 0.0,
            default => throw new \InvalidArgumentException('Unknown academic grade.'),
        };
    }

    public function cumulativeGpa(User $student): float
    {
        $results = Result::where('status', 'approved')
            ->whereHas('registration', fn ($query) => $query->where('student_id', $student->id))
            ->with('registration.course')
            ->get();
        $creditUnits = $results->sum(fn (Result $result) => $result->registration->course->credit_units);

        if ($creditUnits === 0) {
            return 0.0;
        }

        $weightedPoints = $results->sum(fn (Result $result) =>
            $this->gradePointFor($result->grade) * $result->registration->course->credit_units
        );

        return round($weightedPoints / $creditUnits, 2);
    }
}