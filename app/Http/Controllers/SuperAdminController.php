<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class SuperAdminController extends Controller
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
        ]);

        Department::create($data);

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

        return back()->with('status', 'Department Admin account created.');
    }
}
