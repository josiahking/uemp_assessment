<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Services\AcademicCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    public function __construct(private readonly AcademicCalculator $calculator) {}

    public function courses(): View
    {
        $student = auth()->user();

        return view('student.courses', [
            'courses' => Course::where('department_id', $student->department_id)
                ->where('active', true)
                ->with('lecturers')
                ->orderBy('code')
                ->get(),
            'registrations' => $student->courseRegistrations()->with(['course', 'result'])->latest()->get(),
            'gpa' => $this->calculator->cumulativeGpa($student),
        ]);
    }

    public function register(Request $request, Course $course): RedirectResponse
    {
        $student = $request->user();
        $course = Course::where('department_id', $student->department_id)
            ->where('active', true)
            ->findOrFail($course->id);

        $data = $request->validate([
            'academic_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', Rule::in(['First', 'Second', 'Summer'])],
        ]);

        $alreadyRegistered = CourseRegistration::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('academic_year', $data['academic_year'])
            ->where('semester', $data['semester'])
            ->exists();

        if ($alreadyRegistered) {
            return back()->withErrors(['course_id' => 'You already registered for this course in that semester.']);
        }

        CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_year' => $data['academic_year'],
            'semester' => $data['semester'],
            'status' => 'pending',
        ]);

        return back()->with('status', 'Course registration submitted for review.');
    }
}
