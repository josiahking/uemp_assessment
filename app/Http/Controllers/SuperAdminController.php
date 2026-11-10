<?php

namespace App\Http\Controllers;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Result;
use App\Models\Semester;
use App\Models\SystemActivity;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\StudentNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminController extends Controller
{
    public function dashboard(): View
    {
        return view('super-admin.dashboard', [
            'faculties' => Faculty::count(),
            'departments' => Department::count(),
            'students' => User::role('Student')->count(),
            'lecturers' => User::role('Lecturer')->count(),
            'courses' => Course::count(),
            'activeSessions' => AcademicSession::where('active', true)->count(),
            'pendingRegistrations' => CourseRegistration::where('status', 'pending')->count(),
            'submittedResults' => Result::where('status', 'submitted')->count(),
            'activities' => SystemActivity::with('actor')->latest('created_at')->limit(12)->get(),
        ]);
    }

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

    public function academics(): View
    {
        return view('super-admin.academics', [
            'faculties' => Faculty::with('departments')->orderBy('name')->get(),
            'departments' => Department::with(['faculty', 'programs', 'academicLevels'])->orderBy('name')->get(),
            'programs' => AcademicProgram::with(['department', 'levels'])->orderBy('name')->get(),
            'levels' => AcademicLevel::with('department')->orderBy('sort_order')->orderBy('name')->get(),
            'sessions' => AcademicSession::with('semesters')->orderByDesc('starts_at')->get(),
        ]);
    }

    public function storeFaculty(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash:ascii', 'unique:faculties,code'],
        ]);
        $faculty = Faculty::create($data);
        app(ActivityLogger::class)->record('faculty.created', $faculty, $data);

        return back()->with('status', 'Faculty created.');
    }

    public function updateFaculty(Request $request, Faculty $faculty): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash:ascii', Rule::unique('faculties', 'code')->ignore($faculty->id)],
            'active' => ['required', 'boolean'],
        ]);
        $before = $faculty->only(['name', 'code', 'active']);
        $faculty->update($data);
        app(ActivityLogger::class)->record('faculty.updated', $faculty, ['before' => $before, 'after' => $data]);

        return back()->with('status', 'Faculty updated.');
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

    public function storeProgram(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'alpha_dash:ascii'],
            'academic_level_ids' => ['sometimes', 'array'],
            'academic_level_ids.*' => ['integer', Rule::exists('academic_levels', 'id')->where('department_id', $request->input('department_id'))],
        ]);
        $program = AcademicProgram::create([
            'department_id' => $data['department_id'],
            'name' => $data['name'],
            'code' => $data['code'],
            'active' => true,
        ]);
        $program->levels()->sync($data['academic_level_ids'] ?? []);
        app(ActivityLogger::class)->record('program.created', $program, ['level_ids' => $data['academic_level_ids'] ?? []]);

        return back()->with('status', 'Academic program created.');
    }

    public function updateProgram(Request $request, AcademicProgram $program): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'alpha_dash:ascii', Rule::unique('academic_programs')->where('department_id', $program->department_id)->ignore($program->id)],
            'active' => ['required', 'boolean'],
            'academic_level_ids' => ['sometimes', 'array'],
            'academic_level_ids.*' => ['integer', Rule::exists('academic_levels', 'id')->where('department_id', $program->department_id)],
        ]);
        $program->update(collect($data)->except('academic_level_ids')->all());
        $program->levels()->sync($data['academic_level_ids'] ?? []);
        app(ActivityLogger::class)->record('program.updated', $program, ['level_ids' => $data['academic_level_ids'] ?? []]);

        return back()->with('status', 'Academic program updated.');
    }

    public function storeLevel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        $level = AcademicLevel::create([...$data, 'active' => true]);
        app(ActivityLogger::class)->record('academic_level.created', $level, $data);

        return back()->with('status', 'Academic level created.');
    }

    public function updateLevel(Request $request, AcademicLevel $level): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100'],
            'active' => ['required', 'boolean'],
        ]);
        $level->update($data);
        app(ActivityLogger::class)->record('academic_level.updated', $level, $data);

        return back()->with('status', 'Academic level updated.');
    }

    public function storeSession(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/', 'unique:academic_sessions,name'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'active' => ['sometimes', 'boolean'],
        ]);
        $session = DB::transaction(function () use ($data) {
            if ($data['active'] ?? false) {
                AcademicSession::where('active', true)->update(['active' => false]);
            }

            return AcademicSession::create([...$data, 'active' => $data['active'] ?? false]);
        });
        app(ActivityLogger::class)->record('academic_session.created', $session, $data);

        return back()->with('status', 'Academic session created.');
    }

    public function updateSession(Request $request, AcademicSession $session): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/', Rule::unique('academic_sessions', 'name')->ignore($session->id)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'active' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($session, $data) {
            if ($data['active']) {
                AcademicSession::whereKeyNot($session->id)->where('active', true)->update(['active' => false]);
            }
            $session->update($data);
        });
        app(ActivityLogger::class)->record('academic_session.updated', $session, $data);

        return back()->with('status', 'Academic session updated.');
    }

    public function storeSemester(Request $request, AcademicSession $session): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:30', Rule::in(['First', 'Second', 'Summer'])],
            'starts_at' => ['required', 'date', 'after_or_equal:'.$session->starts_at->toDateString(), 'before:'.$session->ends_at->toDateString()],
            'ends_at' => ['required', 'date', 'after:starts_at', 'before_or_equal:'.$session->ends_at->toDateString()],
            'active' => ['sometimes', 'boolean'],
        ]);
        $semester = $session->semesters()->create([...$data, 'active' => $data['active'] ?? false]);
        app(ActivityLogger::class)->record('semester.created', $semester, $data);

        return back()->with('status', 'Semester created.');
    }

    public function updateSemester(Request $request, Semester $semester): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:30', Rule::in(['First', 'Second', 'Summer'])],
            'starts_at' => ['required', 'date', 'after_or_equal:'.$semester->academicSession->starts_at->toDateString()],
            'ends_at' => ['required', 'date', 'after:starts_at', 'before_or_equal:'.$semester->academicSession->ends_at->toDateString()],
            'active' => ['required', 'boolean'],
        ]);
        $semester->update($data);
        app(ActivityLogger::class)->record('semester.updated', $semester, $data);

        return back()->with('status', 'Semester updated.');
    }

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

    public function activities(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:100'],
            'actor_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $query = SystemActivity::with('actor')->latest('created_at');

        if (! empty($filters['q'])) {
            $search = mb_strtolower($filters['q']);
            $query->where(fn ($activities) => $activities
                ->whereRaw('LOWER(action) LIKE ?', ['%'.$search.'%'])
                ->orWhereRaw('LOWER(subject_type) LIKE ?', ['%'.$search.'%'])
                ->orWhereHas('actor', fn ($actors) => $actors->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%'])));
        }
        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (! empty($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return view('super-admin.activities', [
            'activities' => $query->paginate(20)->withQueryString(),
            'filters' => $filters,
            'actions' => SystemActivity::query()->distinct()->orderBy('action')->pluck('action'),
            'actors' => User::whereIn('id', SystemActivity::query()->select('actor_id')->whereNotNull('actor_id')->distinct())
                ->orderBy('name')->get(['id', 'name']),
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
        $data = $request->validate($rules);

        abort_if($data['role'] !== 'Super Admin' && empty($data['department_id']), 422, 'A department is required for this role.');

        if ($data['role'] === 'Student') {
            $program = AcademicProgram::where('department_id', $data['department_id'])->findOrFail($data['academic_program_id']);
            $level = AcademicLevel::where('department_id', $data['department_id'])->findOrFail($data['academic_level_id']);
            abort_if(! $program->levels()->whereKey($level->id)->exists(), 422, 'The selected level is not offered by this program.');
            $data['program'] = $program->name;
            $data['academic_level'] = $level->name;
            $data['student_number'] = app(StudentNumberGenerator::class)->next();
        }

        $account = User::create([
            ...$data,
            'department_id' => $data['role'] === 'Super Admin' ? null : $data['department_id'],
            'account_status' => 'active',
        ]);
        $account->assignRole($data['role']);
        app(ActivityLogger::class)->record('account.created', $account, ['role' => $data['role'], 'department_id' => $account->department_id]);

        return back()->with('status', 'Account created.');
    }

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
        $data = $request->validate($rules);

        abort_if($user->is($request->user()) && ($data['role'] !== 'Super Admin' || $data['account_status'] !== 'active'), 422, 'You cannot remove your own active Super Admin access.');
        abort_if($data['role'] !== 'Super Admin' && ! $data['department_id'], 422, 'A department is required for this role.');

        $studentFields = [];
        if ($data['role'] === 'Student') {
            $program = AcademicProgram::where('department_id', $data['department_id'])->findOrFail($data['academic_program_id']);
            $level = AcademicLevel::where('department_id', $data['department_id'])->findOrFail($data['academic_level_id']);
            abort_if(! $program->levels()->whereKey($level->id)->exists(), 422, 'The selected level is not offered by this program.');
            $studentFields = [
                'student_number' => $user->student_number ?? app(StudentNumberGenerator::class)->next(),
                'academic_program_id' => $program->id,
                'academic_level_id' => $level->id,
                'program' => $program->name,
                'academic_level' => $level->name,
            ];
        }

        $before = [
            'role' => $user->getRoleNames()->first(),
            'account_status' => $user->account_status,
            'department_id' => $user->department_id,
            'academic_program_id' => $user->academic_program_id,
            'academic_level_id' => $user->academic_level_id,
        ];
        $user->update([
            'account_status' => $data['account_status'],
            'department_id' => $data['role'] === 'Super Admin' ? null : $data['department_id'],
            ...$studentFields,
        ]);
        $user->syncRoles($data['role']);
        app(ActivityLogger::class)->record('account.access_updated', $user, ['before' => $before, 'after' => $data]);

        return back()->with('status', 'Account access updated.');
    }
}
