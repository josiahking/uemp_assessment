<?php

namespace App\Http\Controllers;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Semester;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperAdminAcademicController extends Controller
{
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
}
