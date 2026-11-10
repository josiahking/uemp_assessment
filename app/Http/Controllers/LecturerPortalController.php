<?php

namespace App\Http\Controllers;

use App\Models\CourseRegistration;
use App\Models\Result;
use App\Models\ResultEvent;
use App\Services\AcademicCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LecturerPortalController extends Controller
{
    public function __construct(private readonly AcademicCalculator $calculator) {}

    public function courses(): View
    {
        $lecturer = auth()->user();

        return view('lecturer.courses', [
            'courses' => $lecturer->courses()->with('department')->orderBy('code')->get(),
            'registrations' => CourseRegistration::where('status', 'confirmed')
                ->whereHas('course.lecturers', fn ($query) => $query->where('users.id', $lecturer->id))
                ->with(['student', 'course', 'result'])
                ->latest()
                ->get(),
        ]);
    }

    public function submitResult(Request $request, CourseRegistration $registration): RedirectResponse
    {
        $lecturer = $request->user();
        $registration = CourseRegistration::where('status', 'confirmed')
            ->whereHas('course.lecturers', fn ($query) => $query->where('users.id', $lecturer->id))
            ->with('result')
            ->findOrFail($registration->id);

        abort_if($registration->result && ! in_array($registration->result->status, ['draft', 'returned'], true), 409);

        $data = $request->validate([
            'ca_score' => ['required', 'numeric', 'between:0,40'],
            'exam_score' => ['required', 'numeric', 'between:0,60'],
        ]);

        $total = round((float) $data['ca_score'] + (float) $data['exam_score'], 2);
        $result = $registration->result ?? new Result(['course_registration_id' => $registration->id]);
        $previousStatus = $result->status;
        $result->fill([
            'submitted_by' => $lecturer->id,
            'ca_score' => $data['ca_score'],
            'exam_score' => $data['exam_score'],
            'total_score' => $total,
            'grade' => $this->calculator->gradeFor($total),
            'status' => 'submitted',
            'review_note' => null,
            'submitted_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
        ])->save();
        ResultEvent::create([
            'result_id' => $result->id,
            'actor_id' => $lecturer->id,
            'event' => 'submitted',
            'from_status' => $previousStatus,
            'to_status' => 'submitted',
            'metadata' => ['ca_score' => $data['ca_score'], 'exam_score' => $data['exam_score']],
        ]);

        return back()->with('status', 'Result submitted for departmental review.');
    }

}