<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class SuperAdminDepartmentController extends Controller
{
    public function departments(): View
    {
        return view('super-admin.departments', [
            'departments' => Department::withCount('users')->orderBy('name')->get(),
            'administrators' => User::role('Department Admin')->with('department')->orderBy('name')->get(),
        ]);
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash:ascii', 'unique:departments,code'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
        ]);

        $department = Department::create($data);
        app(ActivityLogger::class)->record('department.created', $department, $data);

        return back()->with('status', 'Department created.');
    }

    public function storeAdministrator(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ]);

        $administrator = User::create($data);
        $administrator->assignRole(Role::findOrCreate('Department Admin', 'web'));
        app(ActivityLogger::class)->record('department_admin.created', $administrator, ['department_id' => $data['department_id']]);

        return back()->with('status', 'Department Admin account created.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash:ascii', Rule::unique('departments', 'code')->ignore($department->id)],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
        ]);
        $department->update($data);
        app(ActivityLogger::class)->record('department.updated', $department, $data);

        return back()->with('status', 'Department updated.');
    }
}
