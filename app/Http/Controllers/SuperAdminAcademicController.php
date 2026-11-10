<?php

namespace App\Http\Controllers;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Semester;
use App\Services\AcademicStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperAdminAcademicController extends Controller
{
    public function __construct(private readonly AcademicStructureService $academicStructure) {}

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
        $this->academicStructure->createFaculty($data);

        return back()->with('status', 'Faculty created.');
    }

    public function updateFaculty(Request $request, Faculty $faculty): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash:ascii', Rule::unique('faculties', 'code')->ignore($faculty->id)],
            'active' => ['required', 'boolean'],
        ]);
        $this->academicStructure->updateFaculty($faculty, $data);

        return back()->with('status', 'Faculty updated.');
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
        $this->academicStructure->createProgram($data);

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
        $this->academicStructure->updateProgram($program, $data);

        return back()->with('status', 'Academic program updated.');
    }

    public function storeLevel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        $this->academicStructure->createLevel($data);

        return back()->with('status', 'Academic level created.');
    }

    public function updateLevel(Request $request, AcademicLevel $level): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100'],
            'active' => ['required', 'boolean'],
        ]);
        $this->academicStructure->updateLevel($level, $data);

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
        $this->academicStructure->createSession($data);

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
        $this->academicStructure->updateSession($session, $data);

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
        $this->academicStructure->createSemester($session, $data);

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
        $this->academicStructure->updateSemester($semester, $data);

        return back()->with('status', 'Semester updated.');
    }
}
