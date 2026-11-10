<?php

namespace App\Http\Controllers;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\Department;
use App\Services\DepartmentAcademicService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentAcademicController extends Controller
{
    public function __construct(private readonly DepartmentAcademicService $departmentAcademic) {}

    public function academics(): View
    {
        $user = auth()->user();
        $departmentId = $user->hasRole('Super Admin') ? null : $user->department_id;

        return view('admin.academics', [
            'departments' => $user->hasRole('Super Admin') ? Department::orderBy('name')->get() : collect(),
            'programs' => AcademicProgram::with(['department', 'levels'])
                ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
                ->orderBy('name')->get(),
            'levels' => AcademicLevel::with('department')
                ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
                ->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function storeAcademicLevel(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $data = $request->validate([
            'department_id' => [$isSuperAdmin ? 'required' : 'prohibited', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);
        $departmentId = $isSuperAdmin ? $data['department_id'] : $user->department_id;
        $this->departmentAcademic->createLevel($data, (int) $departmentId);

        return back()->with('status', 'Academic level created.');
    }

    public function updateAcademicLevel(Request $request, AcademicLevel $level): RedirectResponse
    {
        $level = $this->academicLevelsForDepartment()->findOrFail($level->id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'active' => ['required', 'boolean'],
        ]);
        $this->departmentAcademic->updateLevel($level, $data);

        return back()->with('status', 'Academic level updated.');
    }

    public function storeProgram(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $departmentId = $isSuperAdmin ? $request->input('department_id') : $user->department_id;
        $data = $request->validate([
            'department_id' => [$isSuperAdmin ? 'required' : 'prohibited', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'alpha_dash:ascii', Rule::unique('academic_programs')->where('department_id', $departmentId)],
            'academic_level_ids' => ['sometimes', 'array'],
            'academic_level_ids.*' => ['integer', Rule::exists('academic_levels', 'id')->where('department_id', $departmentId)],
        ]);
        $this->departmentAcademic->createProgram($data, (int) $departmentId);

        return back()->with('status', 'Academic program created.');
    }

    public function updateProgram(Request $request, AcademicProgram $program): RedirectResponse
    {
        $program = $this->academicProgramsForDepartment()->findOrFail($program->id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'alpha_dash:ascii', Rule::unique('academic_programs')->where('department_id', $program->department_id)->ignore($program->id)],
            'active' => ['required', 'boolean'],
            'academic_level_ids' => ['sometimes', 'array'],
            'academic_level_ids.*' => ['integer', Rule::exists('academic_levels', 'id')->where('department_id', $program->department_id)],
        ]);
        $this->departmentAcademic->updateProgram($program, $data);

        return back()->with('status', 'Academic program updated.');
    }
}
