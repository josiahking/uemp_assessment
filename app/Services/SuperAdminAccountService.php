<?php

namespace App\Services;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\User;

class SuperAdminAccountService
{
    public function __construct(
        private readonly StudentNumberGenerator $studentNumberGenerator,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function create(array $data): User
    {
        abort_if($data['role'] !== 'Super Admin' && empty($data['department_id']), 422, 'A department is required for this role.');
        $studentFields = $this->studentFields($data, null);
        $account = User::create([
            ...$data,
            ...$studentFields,
            'department_id' => $data['role'] === 'Super Admin' ? null : $data['department_id'],
            'account_status' => 'active',
        ]);
        $account->assignRole($data['role']);
        $this->activityLogger->record('account.created', $account, ['role' => $data['role'], 'department_id' => $account->department_id]);

        return $account;
    }

    public function update(User $account, User $actor, array $data): void
    {
        abort_if($account->is($actor) && ($data['role'] !== 'Super Admin' || $data['account_status'] !== 'active'), 422, 'You cannot remove your own active Super Admin access.');
        abort_if($data['role'] !== 'Super Admin' && ! $data['department_id'], 422, 'A department is required for this role.');

        $studentFields = $this->studentFields($data, $account);
        $before = [
            'role' => $account->getRoleNames()->first(),
            'account_status' => $account->account_status,
            'department_id' => $account->department_id,
            'academic_program_id' => $account->academic_program_id,
            'academic_level_id' => $account->academic_level_id,
        ];
        $account->update([
            'account_status' => $data['account_status'],
            'department_id' => $data['role'] === 'Super Admin' ? null : $data['department_id'],
            ...$studentFields,
        ]);
        $account->syncRoles($data['role']);
        $this->activityLogger->record('account.access_updated', $account, ['before' => $before, 'after' => $data]);
    }

    private function studentFields(array $data, ?User $account): array
    {
        if ($data['role'] !== 'Student') {
            return [];
        }

        $program = AcademicProgram::where('department_id', $data['department_id'])->findOrFail($data['academic_program_id']);
        $level = AcademicLevel::where('department_id', $data['department_id'])->findOrFail($data['academic_level_id']);
        abort_if(! $program->levels()->whereKey($level->id)->exists(), 422, 'The selected level is not offered by this program.');

        return [
            'student_number' => $account?->student_number ?? $this->studentNumberGenerator->next(),
            'academic_program_id' => $program->id,
            'academic_level_id' => $level->id,
            'program' => $program->name,
            'academic_level' => $level->name,
        ];
    }
}
