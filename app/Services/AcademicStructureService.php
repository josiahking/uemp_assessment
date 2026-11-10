<?php

namespace App\Services;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\AcademicSession;
use App\Models\Faculty;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;

class AcademicStructureService
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function createFaculty(array $data): Faculty
    {
        $faculty = Faculty::create($data);
        $this->activityLogger->record('faculty.created', $faculty, $data);

        return $faculty;
    }

    public function updateFaculty(Faculty $faculty, array $data): void
    {
        $before = $faculty->only(['name', 'code', 'active']);
        $faculty->update($data);
        $this->activityLogger->record('faculty.updated', $faculty, ['before' => $before, 'after' => $data]);
    }

    public function createProgram(array $data): AcademicProgram
    {
        $program = AcademicProgram::create([
            'department_id' => $data['department_id'],
            'name' => $data['name'],
            'code' => $data['code'],
            'active' => true,
        ]);
        $levelIds = $data['academic_level_ids'] ?? [];
        $program->levels()->sync($levelIds);
        $this->activityLogger->record('program.created', $program, ['level_ids' => $levelIds]);

        return $program;
    }

    public function updateProgram(AcademicProgram $program, array $data): void
    {
        $program->update(collect($data)->except('academic_level_ids')->all());
        $levelIds = $data['academic_level_ids'] ?? [];
        $program->levels()->sync($levelIds);
        $this->activityLogger->record('program.updated', $program, ['level_ids' => $levelIds]);
    }

    public function createLevel(array $data): AcademicLevel
    {
        $level = AcademicLevel::create([...$data, 'active' => true]);
        $this->activityLogger->record('academic_level.created', $level, $data);

        return $level;
    }

    public function updateLevel(AcademicLevel $level, array $data): void
    {
        $level->update($data);
        $this->activityLogger->record('academic_level.updated', $level, $data);
    }

    public function createSession(array $data): AcademicSession
    {
        $session = DB::transaction(function () use ($data) {
            if ($data['active'] ?? false) {
                AcademicSession::where('active', true)->update(['active' => false]);
            }

            return AcademicSession::create([...$data, 'active' => $data['active'] ?? false]);
        });
        $this->activityLogger->record('academic_session.created', $session, $data);

        return $session;
    }

    public function updateSession(AcademicSession $session, array $data): void
    {
        DB::transaction(function () use ($session, $data) {
            if ($data['active']) {
                AcademicSession::whereKeyNot($session->id)->where('active', true)->update(['active' => false]);
            }
            $session->update($data);
        });
        $this->activityLogger->record('academic_session.updated', $session, $data);
    }

    public function createSemester(AcademicSession $session, array $data): Semester
    {
        $semester = $session->semesters()->create([...$data, 'active' => $data['active'] ?? false]);
        $this->activityLogger->record('semester.created', $semester, $data);

        return $semester;
    }

    public function updateSemester(Semester $semester, array $data): void
    {
        $semester->update($data);
        $this->activityLogger->record('semester.updated', $semester, $data);
    }
}
