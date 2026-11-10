<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Result;
use App\Models\SystemActivity;
use App\Models\User;
use Illuminate\View\View;

class SuperAdminDashboardController extends Controller
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
}
