<?php

namespace App\Http\Controllers;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\User;
use App\Services\SuperAdminAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class SuperAdminAccountController extends Controller
{
    public function __construct(private readonly SuperAdminAccountService $accountService) {}

    public function accounts(Request $request): View
    {
        $rawSearch = $request->query('q', '');
        $rawRole = $request->query('role', '');
        $rawStatus = $request->query('account_status', '');
        $rawDepartmentId = $request->query('department_id', '');
        $filters = [
            'q' => is_string($rawSearch) ? mb_substr(trim($rawSearch), 0, 100) : '',
            'role' => is_string($rawRole) ? $rawRole : '',
            'account_status' => is_string($rawStatus) ? $rawStatus : '',
            'department_id' => is_string($rawDepartmentId) ? $rawDepartmentId : '',
        ];
        $query = User::with(['department', 'academicProgram', 'academicLevel', 'roles']);

        if ($filters['q'] !== '') {
            $search = mb_strtolower($filters['q']);
            $query->where(fn ($users) => $users
                ->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%'])
                ->orWhereRaw('LOWER(email) LIKE ?', ['%'.$search.'%'])
                ->orWhereRaw('LOWER(student_number) LIKE ?', ['%'.$search.'%']));
        }
        if (is_string($filters['role']) && Role::where('guard_name', 'web')->where('name', $filters['role'])->exists()) {
            $query->role($filters['role']);
        }
        if (in_array($filters['account_status'], ['active', 'deactivated', 'suspended'], true)) {
            $query->where('account_status', $filters['account_status']);
        }
        if (filter_var($filters['department_id'], FILTER_VALIDATE_INT)) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        return view('super-admin.accounts', [
            'users' => $query->orderBy('name')->paginate(20)->withQueryString(),
            'departments' => Department::orderBy('name')->get(),
            'programs' => AcademicProgram::where('active', true)->orderBy('name')->get(),
            'levels' => AcademicLevel::where('active', true)->orderBy('sort_order')->get(),
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $role = $request->input('role');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ];
        if ($role === 'Student') {
            $rules += [
                'academic_program_id' => ['required', 'integer', 'exists:academic_programs,id'],
                'academic_level_id' => ['required', 'integer', 'exists:academic_levels,id'],
            ];
        }
        $this->accountService->create($request->validate($rules));

        return back()->with('status', 'Account created.');
    }

    public function updateAccount(Request $request, User $user): RedirectResponse
    {
        $role = $request->input('role');
        $departmentId = $request->input('department_id');
        $rules = [
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'account_status' => ['required', Rule::in(['active', 'deactivated', 'suspended'])],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ];
        if ($role === 'Student') {
            $rules += [
                'academic_program_id' => ['required', 'integer', Rule::exists('academic_programs', 'id')->where('department_id', $departmentId)],
                'academic_level_id' => ['required', 'integer', Rule::exists('academic_levels', 'id')->where('department_id', $departmentId)],
            ];
        }
        $this->accountService->update($user, $request->user(), $request->validate($rules));

        return back()->with('status', 'Account access updated.');
    }
}
