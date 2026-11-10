<?php

namespace App\Services;

use App\Models\CourseOffering;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StudentCourseOfferingAvailability
{
    public function for(User $student): Builder
    {
        return CourseOffering::query()
            ->where('active', true)
            ->whereHas('course', fn ($query) => $query
                ->where('department_id', $student->department_id)
                ->where('active', true))
            ->whereHas('semester', fn ($query) => $query
                ->where('active', true)
                ->whereHas('academicSession', fn ($sessions) => $sessions->where('active', true)))
            ->where(fn ($query) => $query->whereNull('academic_program_id')
                ->orWhere(fn ($programs) => $programs
                    ->where('academic_program_id', $student->academic_program_id)
                    ->whereHas('program', fn ($program) => $program
                        ->where('active', true)
                        ->whereHas('levels', fn ($levels) => $levels
                            ->whereKey($student->academic_level_id)
                            ->where('active', true)))))
            ->where(fn ($query) => $query->whereNull('academic_level_id')
                ->orWhere(fn ($levels) => $levels
                    ->where('academic_level_id', $student->academic_level_id)
                    ->whereHas('academicLevel', fn ($level) => $level->where('active', true))));
    }
}
