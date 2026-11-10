<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentAdminController;
use App\Http\Controllers\LecturerPortalController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:Department Admin|Super Admin')->prefix('admin')->name('admin.')->group(function () {
        Route::middleware('permission:manage academic catalog')->group(function () {
            Route::get('/academics', [DepartmentAdminController::class, 'academics'])->name('academics.index');
            Route::post('/academic-levels', [DepartmentAdminController::class, 'storeAcademicLevel'])->name('academic-levels.store');
            Route::patch('/academic-levels/{level}', [DepartmentAdminController::class, 'updateAcademicLevel'])->name('academic-levels.update');
            Route::post('/programs', [DepartmentAdminController::class, 'storeProgram'])->name('programs.store');
            Route::patch('/programs/{program}', [DepartmentAdminController::class, 'updateProgram'])->name('programs.update');
        });
        Route::middleware('permission:manage students')->group(function () {
            Route::get('/students', [DepartmentAdminController::class, 'students'])->name('students.index');
            Route::post('/students', [DepartmentAdminController::class, 'storeStudent'])->name('students.store');
            Route::patch('/students/{student}', [DepartmentAdminController::class, 'updateStudent'])->name('students.update');
        });
        Route::middleware('permission:manage lecturers')->group(function () {
            Route::get('/lecturers', [DepartmentAdminController::class, 'lecturers'])->name('lecturers.index');
            Route::post('/lecturers', [DepartmentAdminController::class, 'storeLecturer'])->name('lecturers.store');
            Route::patch('/lecturers/{lecturer}', [DepartmentAdminController::class, 'updateLecturer'])->name('lecturers.update');
        });
        Route::middleware('permission:manage courses')->group(function () {
            Route::get('/courses', [DepartmentAdminController::class, 'courses'])->name('courses.index');
            Route::post('/courses', [DepartmentAdminController::class, 'storeCourse'])->name('courses.store');
            Route::patch('/courses/{course}', [DepartmentAdminController::class, 'updateCourse'])->name('courses.update');
            Route::post('/courses/{course}/offerings', [DepartmentAdminController::class, 'storeOffering'])->name('courses.offerings.store');
            Route::patch('/offerings/{offering}', [DepartmentAdminController::class, 'updateOffering'])->name('offerings.update');
            Route::post('/courses/{course}/lecturers', [DepartmentAdminController::class, 'assignLecturer'])->name('courses.assign-lecturer');
            Route::delete('/courses/{course}/lecturers/{lecturer}', [DepartmentAdminController::class, 'unassignLecturer'])->name('courses.unassign-lecturer');
        });
        Route::middleware('permission:review registrations')->group(function () {
            Route::get('/registrations', [DepartmentAdminController::class, 'registrations'])->name('registrations.index');
            Route::patch('/registrations/{registration}', [DepartmentAdminController::class, 'updateRegistration'])->name('registrations.update');
        });
        Route::middleware('permission:review results')->group(function () {
            Route::get('/results', [DepartmentAdminController::class, 'results'])->name('results.index');
            Route::patch('/results/{result}', [DepartmentAdminController::class, 'reviewResult'])->name('results.review');
        });
        Route::middleware('permission:view reports')->get('/reports', [DepartmentAdminController::class, 'reports'])->name('reports.index');
    });

    Route::middleware('role:Student')->prefix('student')->name('student.')->group(function () {
        Route::get('/courses', [StudentPortalController::class, 'courses'])->middleware('permission:view own records')->name('courses.index');
        Route::post('/offerings/{offering}/registrations', [StudentPortalController::class, 'register'])->middleware('permission:register courses')->name('registrations.store');
    });

    Route::middleware('role:Lecturer')->prefix('lecturer')->name('lecturer.')->group(function () {
        Route::get('/courses', [LecturerPortalController::class, 'courses'])->middleware('permission:view assigned courses')->name('courses.index');
        Route::post('/registrations/{registration}/result', [LecturerPortalController::class, 'submitResult'])->middleware('permission:submit results')->name('results.store');
    });

    Route::middleware('role:Super Admin')->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/academics', [SuperAdminController::class, 'academics'])->name('academics.index');
        Route::get('/departments', [SuperAdminController::class, 'departments'])->name('departments.index');
        Route::post('/departments', [SuperAdminController::class, 'storeDepartment'])->name('departments.store');
        Route::patch('/departments/{department}', [SuperAdminController::class, 'updateDepartment'])->name('departments.update');
        Route::post('/administrators', [SuperAdminController::class, 'storeAdministrator'])->name('administrators.store');
        Route::post('/faculties', [SuperAdminController::class, 'storeFaculty'])->name('faculties.store');
        Route::patch('/faculties/{faculty}', [SuperAdminController::class, 'updateFaculty'])->name('faculties.update');
        Route::post('/programs', [SuperAdminController::class, 'storeProgram'])->name('programs.store');
        Route::patch('/programs/{program}', [SuperAdminController::class, 'updateProgram'])->name('programs.update');
        Route::post('/levels', [SuperAdminController::class, 'storeLevel'])->name('levels.store');
        Route::patch('/levels/{level}', [SuperAdminController::class, 'updateLevel'])->name('levels.update');
        Route::post('/academic-sessions', [SuperAdminController::class, 'storeSession'])->name('academic-sessions.store');
        Route::patch('/academic-sessions/{session}', [SuperAdminController::class, 'updateSession'])->name('academic-sessions.update');
        Route::post('/academic-sessions/{session}/semesters', [SuperAdminController::class, 'storeSemester'])->name('semesters.store');
        Route::patch('/semesters/{semester}', [SuperAdminController::class, 'updateSemester'])->name('semesters.update');
        Route::get('/accounts', [SuperAdminController::class, 'accounts'])->name('accounts.index');
        Route::post('/accounts', [SuperAdminController::class, 'storeAccount'])->name('accounts.store');
        Route::patch('/accounts/{user}', [SuperAdminController::class, 'updateAccount'])->name('accounts.update');
        Route::get('/activity', [SuperAdminController::class, 'activities'])->name('activities.index');
        Route::get('/roles', [SuperAdminController::class, 'roles'])->name('roles.index');
        Route::post('/roles', [SuperAdminController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{role}/permissions', [SuperAdminController::class, 'updateRolePermissions'])->name('roles.permissions');
    });
});
