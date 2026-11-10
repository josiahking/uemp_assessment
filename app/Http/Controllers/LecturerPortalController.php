<?php

namespace App\Http\Controllers;

use App\Models\CourseRegistration;
use App\Services\LecturerResultSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LecturerPortalController extends Controller
{
    public function __construct(private readonly LecturerResultSubmission $resultSubmission) {}

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

        $data = $request->validate([
            'ca_score' => ['required', 'numeric', 'between:0,40'],
            'exam_score' => ['required', 'numeric', 'between:0,60'],
        ]);
        $this->resultSubmission->submit($registration, $lecturer, $data);

        return back()->with('status', 'Result submitted for departmental review.');
    }
}
