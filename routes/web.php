<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentCatalogController;
use App\Http\Controllers\DepartmentPeopleController;
use App\Http\Controllers\DepartmentReviewController;
use App\Http\Controllers\LecturerPortalController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\SuperAdminAcademicController;
use App\Http\Controllers\SuperAdminAccountController;
use App\Http\Controllers\SuperAdminActivityController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdminDepartmentController;
use App\Http\Controllers\SuperAdminRoleController;
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
            Route::get('/academics', [DepartmentCatalogController::class, 'academics'])->name('academics.index');
            Route::post('/academic-levels', [DepartmentCatalogController::class, 'storeAcademicLevel'])->name('academic-levels.store');
            Route::patch('/academic-levels/{level}', [DepartmentCatalogController::class, 'updateAcademicLevel'])->name('academic-levels.update');
            Route::post('/programs', [DepartmentCatalogController::class, 'storeProgram'])->name('programs.store');
            Route::patch('/programs/{program}', [DepartmentCatalogController::class, 'updateProgram'])->name('programs.update');
        });
        Route::middleware('permission:manage students')->group(function () {
            Route::get('/students', [DepartmentPeopleController::class, 'students'])->name('students.index');
            Route::post('/students', [DepartmentPeopleController::class, 'storeStudent'])->name('students.store');
            Route::patch('/students/{student}', [DepartmentPeopleController::class, 'updateStudent'])->name('students.update');
        });
        Route::middleware('permission:manage lecturers')->group(function () {
            Route::get('/lecturers', [DepartmentPeopleController::class, 'lecturers'])->name('lecturers.index');
            Route::post('/lecturers', [DepartmentPeopleController::class, 'storeLecturer'])->name('lecturers.store');
            Route::patch('/lecturers/{lecturer}', [DepartmentPeopleController::class, 'updateLecturer'])->name('lecturers.update');
        });
        Route::middleware('permission:manage courses')->group(function () {
            Route::get('/courses', [DepartmentCatalogController::class, 'courses'])->name('courses.index');
            Route::post('/courses', [DepartmentCatalogController::class, 'storeCourse'])->name('courses.store');
            Route::patch('/courses/{course}', [DepartmentCatalogController::class, 'updateCourse'])->name('courses.update');
            Route::post('/courses/{course}/offerings', [DepartmentCatalogController::class, 'storeOffering'])->name('courses.offerings.store');
            Route::patch('/offerings/{offering}', [DepartmentCatalogController::class, 'updateOffering'])->name('offerings.update');
            Route::post('/courses/{course}/lecturers', [DepartmentCatalogController::class, 'assignLecturer'])->name('courses.assign-lecturer');
            Route::delete('/courses/{course}/lecturers/{lecturer}', [DepartmentCatalogController::class, 'unassignLecturer'])->name('courses.unassign-lecturer');
        });
        Route::middleware('permission:review registrations')->group(function () {
            Route::get('/registrations', [DepartmentReviewController::class, 'registrations'])->name('registrations.index');
            Route::patch('/registrations/{registration}', [DepartmentReviewController::class, 'updateRegistration'])->name('registrations.update');
        });
        Route::middleware('permission:review results')->group(function () {
            Route::get('/results', [DepartmentReviewController::class, 'results'])->name('results.index');
            Route::patch('/results/{result}', [DepartmentReviewController::class, 'reviewResult'])->name('results.review');
        });
        Route::middleware('permission:view reports')->get('/reports', [DepartmentReviewController::class, 'reports'])->name('reports.index');
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
        Route::get('/', [SuperAdminDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/academics', [SuperAdminAcademicController::class, 'academics'])->name('academics.index');
        Route::get('/departments', [SuperAdminDepartmentController::class, 'departments'])->name('departments.index');
        Route::post('/departments', [SuperAdminDepartmentController::class, 'storeDepartment'])->name('departments.store');
        Route::patch('/departments/{department}', [SuperAdminDepartmentController::class, 'updateDepartment'])->name('departments.update');
        Route::post('/administrators', [SuperAdminDepartmentController::class, 'storeAdministrator'])->name('administrators.store');
        Route::post('/faculties', [SuperAdminAcademicController::class, 'storeFaculty'])->name('faculties.store');
        Route::patch('/faculties/{faculty}', [SuperAdminAcademicController::class, 'updateFaculty'])->name('faculties.update');
        Route::post('/programs', [SuperAdminAcademicController::class, 'storeProgram'])->name('programs.store');
        Route::patch('/programs/{program}', [SuperAdminAcademicController::class, 'updateProgram'])->name('programs.update');
        Route::post('/levels', [SuperAdminAcademicController::class, 'storeLevel'])->name('levels.store');
        Route::patch('/levels/{level}', [SuperAdminAcademicController::class, 'updateLevel'])->name('levels.update');
        Route::post('/academic-sessions', [SuperAdminAcademicController::class, 'storeSession'])->name('academic-sessions.store');
        Route::patch('/academic-sessions/{session}', [SuperAdminAcademicController::class, 'updateSession'])->name('academic-sessions.update');
        Route::post('/academic-sessions/{session}/semesters', [SuperAdminAcademicController::class, 'storeSemester'])->name('semesters.store');
        Route::patch('/semesters/{semester}', [SuperAdminAcademicController::class, 'updateSemester'])->name('semesters.update');
        Route::get('/accounts', [SuperAdminAccountController::class, 'accounts'])->name('accounts.index');
        Route::post('/accounts', [SuperAdminAccountController::class, 'storeAccount'])->name('accounts.store');
        Route::patch('/accounts/{user}', [SuperAdminAccountController::class, 'updateAccount'])->name('accounts.update');
        Route::get('/activity', [SuperAdminActivityController::class, 'activities'])->name('activities.index');
        Route::get('/roles', [SuperAdminRoleController::class, 'roles'])->name('roles.index');
        Route::post('/roles', [SuperAdminRoleController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{role}/permissions', [SuperAdminRoleController::class, 'updateRolePermissions'])->name('roles.permissions');
    });
});
