<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminRoleController extends Controller
{
    public function roles(): View
    {
        return view('super-admin.roles', [
            'roles' => Role::with('permissions')->where('guard_name', 'web')->orderBy('name')->get(),
            'permissions' => Permission::where('guard_name', 'web')->orderBy('name')->get(),
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);
        app(ActivityLogger::class)->record('role.created', $role, ['permissions' => $data['permissions'] ?? []]);

        return back()->with('status', 'Role created.');
    }

    public function updateRolePermissions(Request $request, Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);
        $data = $request->validate([
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);
        $role->syncPermissions($data['permissions'] ?? []);
        app(ActivityLogger::class)->record('role.permissions_updated', $role, ['permissions' => $data['permissions'] ?? []]);

        return back()->with('status', 'Role permissions updated.');
    }
}
