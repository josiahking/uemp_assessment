<?php

namespace App\Services;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DepartmentPeopleService
{
    public function __construct(
        private readonly StudentNumberGenerator $studentNumberGenerator,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function createAccount(array $data, User $actor, string $role): User
    {
        $departmentId = $actor->hasRole('Super Admin') ? $data['department_id'] : $actor->department_id;
        abort_if(! $departmentId, 422, 'Select a department for this account.');

        if ($role === 'Student') {
            $program = AcademicProgram::where('department_id', $departmentId)->findOrFail($data['academic_program_id']);
            $level = AcademicLevel::where('department_id', $departmentId)->findOrFail($data['academic_level_id']);
            if (! $program->levels()->whereKey($level->id)->exists()) {
                throw ValidationException::withMessages(['academic_level_id' => 'The selected level is not offered by this program.']);
            }
            $data['program'] = $program->name;
            $data['academic_level'] = $level->name;
            $data['student_number'] = $this->studentNumberGenerator->next();
        }

        $data['account_status'] = 'active';
        $account = User::create([...$data, 'department_id' => $departmentId]);
        $account->assignRole($role);
        $this->activityLogger->record(strtolower($role).'.created', $account, ['department_id' => $departmentId]);

        return $account;
    }

    public function updateStudent(User $student, array $data): void
    {
        $program = AcademicProgram::where('department_id', $student->department_id)->findOrFail($data['academic_program_id']);
        $level = AcademicLevel::where('department_id', $student->department_id)->findOrFail($data['academic_level_id']);
        if (! $program->levels()->whereKey($level->id)->exists()) {
            throw ValidationException::withMessages(['academic_level_id' => 'The selected level is not offered by this program.']);
        }
        $student->update([...$data, 'program' => $program->name, 'academic_level' => $level->name]);
        $this->activityLogger->record('student.updated', $student, ['account_status' => $student->account_status]);
    }

    public function updateLecturer(User $lecturer, array $data): void
    {
        $lecturer->update($data);
        $this->activityLogger->record('lecturer.updated', $lecturer, ['account_status' => $lecturer->account_status]);
    }
}
