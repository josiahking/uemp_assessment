<?php

namespace App\Http\Controllers;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentPeopleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentPeopleController extends Controller
{
    public function __construct(private readonly DepartmentPeopleService $people) {}

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
        $this->people->updateStudent($student, $this->validateUserProfile($request, $student->department_id));

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
        $this->people->updateLecturer($lecturer, $data);

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
        $this->people->createAccount($data, $user, $role);

        return back()->with('status', $role.' account created.');
    }

    private function validateUserProfile(Request $request, int $departmentId): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->route('student')?->id)],
            'account_status' => ['required', Rule::in(['active', 'deactivated', 'suspended'])],
            'academic_program_id' => ['required', 'integer', Rule::exists('academic_programs', 'id')->where('department_id', $departmentId)],
            'academic_level_id' => ['required', 'integer', Rule::exists('academic_levels', 'id')->where('department_id', $departmentId)],
        ]);
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
