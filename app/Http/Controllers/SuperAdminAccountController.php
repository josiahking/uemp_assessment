<?php

namespace App\Http\Controllers;

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
        $query = User::whereDoesntHave('roles', fn ($roles) => $roles
            ->where('name', 'Student')
            ->where('guard_name', 'web'))
            ->with(['department', 'roles']);

        if ($filters['q'] !== '') {
            $search = mb_strtolower($filters['q']);
            $query->where(fn ($users) => $users
                ->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%'])
                ->orWhereRaw('LOWER(email) LIKE ?', ['%'.$search.'%']));
        }
        if ($filters['role'] === 'Student') {
            $query->whereRaw('1 = 0');
        } elseif ($filters['role'] !== '' && Role::where('guard_name', 'web')->where('name', $filters['role'])->exists()) {
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
            'roles' => Role::where('guard_name', 'web')->where('name', '!=', 'Student')->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(fn ($query) => $query
                    ->where('guard_name', 'web')
                    ->where('name', '!=', 'Student')),
            ],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ];
        $this->accountService->create($request->validate($rules));

        return back()->with('status', 'Account created.');
    }

    public function updateAccount(Request $request, User $user): RedirectResponse
    {
        $user = User::whereDoesntHave('roles', fn ($roles) => $roles
            ->where('name', 'Student')
            ->where('guard_name', 'web'))
            ->findOrFail($user->id);
        $rules = [
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(fn ($query) => $query
                    ->where('guard_name', 'web')
                    ->where('name', '!=', 'Student')),
            ],
            'account_status' => ['required', Rule::in(['active', 'deactivated', 'suspended'])],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ];
        $this->accountService->update($user, $request->user(), $request->validate($rules));

        return back()->with('status', 'Account access updated.');
    }
}
