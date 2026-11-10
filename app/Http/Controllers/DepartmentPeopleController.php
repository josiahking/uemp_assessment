<?php

namespace App\Http\Controllers;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\StudentNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentPeopleController extends Controller
{
    public function students(): View
    {
        $user = auth()->user();
        $request = request();
        $rawSearch = $request->query('q', '');
        $search = is_string($rawSearch) ? mb_substr(trim($rawSearch), 0, 100) : '';
        $rawStatus = $request->query('account_status');
        $status = is_string($rawStatus) ? $rawStatus : '';
        $studentsQuery = $this->usersForRole('Student')->with(['department', 'academicProgram', 'academicLevel']);

        if ($search !== '') {
            $studentsQuery->where(fn ($query) => $query
                ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])
                ->orWhereRaw('LOWER(email) LIKE ?', ['%'.mb_strtolower($search).'%'])
                ->orWhereRaw('LOWER(student_number) LIKE ?', ['%'.mb_strtolower($search).'%']));
        }
        if (in_array($status, ['active', 'deactivated', 'suspended'], true)) {
            $studentsQuery->where('account_status', $status);
        }

        return view('admin.students.index', [
            'students' => $studentsQuery->orderBy('name')->paginate(20)->withQueryString(),
            'filters' => ['q' => $search, 'account_status' => $status],
            'departments' => $user->hasRole('Super Admin') ? Department::orderBy('name')->get() : collect(),
            'programs' => $this->programsForUser($user),
            'levels' => $this->levelsForUser($user),
        ]);
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        return $this->storeUser($request, 'Student');
    }

    public function updateStudent(Request $request, User $student): RedirectResponse
    {
        $student = $this->usersForRole('Student')->findOrFail($student->id);
        $student->update($this->validateUserProfile($request, $student->department_id));
        app(ActivityLogger::class)->record('student.updated', $student, ['account_status' => $student->account_status]);

        return back()->with('status', 'Student profile updated.');
    }

    public function lecturers(): View
    {
        $user = auth()->user();

        return view('admin.lecturers.index', [
            'lecturers' => $this->usersForRole('Lecturer')->with('department')->orderBy('name')->get(),
            'departments' => $user->hasRole('Super Admin') ? Department::orderBy('name')->get() : collect(),
        ]);
    }

    public function storeLecturer(Request $request): RedirectResponse
    {
        return $this->storeUser($request, 'Lecturer');
    }

    public function updateLecturer(Request $request, User $lecturer): RedirectResponse
    {
        $lecturer = $this->usersForRole('Lecturer')->findOrFail($lecturer->id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($lecturer->id)],
            'faculty' => ['nullable', 'string', 'max:255'],
            'account_status' => ['required', Rule::in(['active', 'deactivated', 'suspended'])],
        ]);
        $lecturer->update($data);
        app(ActivityLogger::class)->record('lecturer.updated', $lecturer, ['account_status' => $lecturer->account_status]);

        return back()->with('status', 'Lecturer profile updated.');
    }

    private function storeUser(Request $request, string $role): RedirectResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $departmentId = $isSuperAdmin ? $request->input('department_id') : $user->department_id;
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'department_id' => [$isSuperAdmin ? 'required' : 'prohibited', 'integer', 'exists:departments,id'],
            'faculty' => ['nullable', 'string', 'max:255'],
        ];

        if ($role === 'Student') {
            $rules += [
                'academic_program_id' => ['required', 'integer', Rule::exists('academic_programs', 'id')->where('department_id', $departmentId)],
                'academic_level_id' => ['required', 'integer', Rule::exists('academic_levels', 'id')->where('department_id', $departmentId)],
            ];
        }

        $data = $request->validate($rules);
        abort_if(! $departmentId, 422, 'Select a department for this account.');

        if ($role === 'Student') {
            $program = AcademicProgram::findOrFail($data['academic_program_id']);
            if (! $program->levels()->whereKey($data['academic_level_id'])->exists()) {
                return back()->withErrors(['academic_level_id' => 'The selected level is not offered by this program.'])->withInput();
            }
            $data['program'] = $program->name;
            $data['academic_level'] = AcademicLevel::findOrFail($data['academic_level_id'])->name;
            $data['student_number'] = app(StudentNumberGenerator::class)->next();
        }
        $data['account_status'] = 'active';

        $account = User::create([...$data, 'department_id' => $departmentId]);
        $account->assignRole($role);
        app(ActivityLogger::class)->record(strtolower($role).'.created', $account, ['department_id' => $departmentId]);

        return back()->with('status', $role.' account created.');
    }

    private function validateUserProfile(Request $request, int $departmentId): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->route('student')?->id)],
            'account_status' => ['required', Rule::in(['active', 'deactivated', 'suspended'])],
            'academic_program_id' => ['required', 'integer', Rule::exists('academic_programs', 'id')->where('department_id', $departmentId)],
            'academic_level_id' => ['required', 'integer', Rule::exists('academic_levels', 'id')->where('department_id', $departmentId)],
        ]);
        $program = AcademicProgram::where('department_id', $departmentId)->findOrFail($data['academic_program_id']);
        if (! $program->levels()->whereKey($data['academic_level_id'])->exists()) {
            return back()->withErrors(['academic_level_id' => 'The selected level is not offered by this program.'])->throwResponse();
        }
        $data['program'] = $program->name;
        $data['academic_level'] = AcademicLevel::where('department_id', $departmentId)->findOrFail($data['academic_level_id'])->name;

        return $data;
    }

    private function usersForRole(string $role)
    {
        $user = auth()->user();

        return User::role($role)
            ->when(! $user->hasRole('Super Admin'), fn ($query) => $query->where('department_id', $user->department_id));
    }

    private function programsForUser(User $user)
    {
        return AcademicProgram::where('active', true)
            ->when(! $user->hasRole('Super Admin'), fn ($query) => $query->where('department_id', $user->department_id))
            ->orderBy('name')->get();
    }

    private function levelsForUser(User $user)
    {
        return AcademicLevel::where('active', true)
            ->when(! $user->hasRole('Super Admin'), fn ($query) => $query->where('department_id', $user->department_id))
            ->orderBy('sort_order')->get();
    }
}