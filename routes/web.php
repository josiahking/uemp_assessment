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

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:Department Admin|Super Admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/students', [DepartmentAdminController::class, 'students'])->name('students.index');
        Route::post('/students', [DepartmentAdminController::class, 'storeStudent'])->name('students.store');
        Route::get('/lecturers', [DepartmentAdminController::class, 'lecturers'])->name('lecturers.index');
        Route::post('/lecturers', [DepartmentAdminController::class, 'storeLecturer'])->name('lecturers.store');
        Route::get('/courses', [DepartmentAdminController::class, 'courses'])->name('courses.index');
        Route::post('/courses', [DepartmentAdminController::class, 'storeCourse'])->name('courses.store');
        Route::post('/courses/{course}/lecturers', [DepartmentAdminController::class, 'assignLecturer'])->name('courses.assign-lecturer');
        Route::get('/registrations', [DepartmentAdminController::class, 'registrations'])->name('registrations.index');
        Route::patch('/registrations/{registration}', [DepartmentAdminController::class, 'updateRegistration'])->name('registrations.update');
        Route::get('/results', [DepartmentAdminController::class, 'results'])->name('results.index');
        Route::patch('/results/{result}', [DepartmentAdminController::class, 'reviewResult'])->name('results.review');
        Route::get('/reports', [DepartmentAdminController::class, 'reports'])->name('reports.index');
    });

    Route::middleware('role:Student')->prefix('student')->name('student.')->group(function () {
        Route::get('/courses', [StudentPortalController::class, 'courses'])->name('courses.index');
        Route::post('/courses/{course}/registrations', [StudentPortalController::class, 'register'])->name('registrations.store');
    });

    Route::middleware('role:Lecturer')->prefix('lecturer')->name('lecturer.')->group(function () {
        Route::get('/courses', [LecturerPortalController::class, 'courses'])->name('courses.index');
        Route::post('/registrations/{registration}/result', [LecturerPortalController::class, 'submitResult'])->name('results.store');
    });

    Route::middleware('role:Super Admin')->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/departments', [SuperAdminController::class, 'departments'])->name('departments.index');
        Route::post('/departments', [SuperAdminController::class, 'storeDepartment'])->name('departments.store');
        Route::post('/administrators', [SuperAdminController::class, 'storeAdministrator'])->name('administrators.store');
    });
});
