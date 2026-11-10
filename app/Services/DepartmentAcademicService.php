<?php

namespace App\Services;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;

class DepartmentAcademicService
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function createLevel(array $data, int $departmentId): AcademicLevel
    {
        $level = AcademicLevel::create([...$data, 'department_id' => $departmentId, 'active' => true]);
        $this->activityLogger->record('academic_level.created', $level, ['department_id' => $departmentId]);

        return $level;
    }

    public function updateLevel(AcademicLevel $level, array $data): void
    {
        $level->update($data);
        $this->activityLogger->record('academic_level.updated', $level, $data);
    }

    public function createProgram(array $data, int $departmentId): AcademicProgram
    {
        $program = AcademicProgram::create([
            'department_id' => $departmentId,
            'name' => $data['name'],
            'code' => $data['code'],
            'active' => true,
        ]);
        $program->levels()->sync($data['academic_level_ids'] ?? []);
        $this->activityLogger->record('program.created', $program, ['level_ids' => $data['academic_level_ids'] ?? []]);

        return $program;
    }

    public function updateProgram(AcademicProgram $program, array $data): void
    {
        $program->update(collect($data)->except('academic_level_ids')->all());
        $program->levels()->sync($data['academic_level_ids'] ?? []);
        $this->activityLogger->record('program.updated', $program, ['level_ids' => $data['academic_level_ids'] ?? []]);
    }
}
