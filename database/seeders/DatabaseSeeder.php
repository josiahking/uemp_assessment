<?php

namespace Database\Seeders;

use App\Models\AcademicLevel;
use App\Models\AcademicProgram;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Result;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage students',
            'manage lecturers',
            'manage courses',
            'review registrations',
            'review results',
            'submit results',
            'view assigned courses',
            'view own records',
            'register courses',
            'view reports',
            'manage university',
            'manage academic catalog',
            'manage academic sessions',
            'manage user accounts',
            'manage roles and permissions',
            'view system activity',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $departmentAdminRole = Role::findOrCreate('Department Admin', 'web');
        $lecturerRole = Role::findOrCreate('Lecturer', 'web');
        $studentRole = Role::findOrCreate('Student', 'web');

        $superAdminRole->syncPermissions($permissions);
        $departmentAdminRole->syncPermissions([
            'manage students', 'manage lecturers', 'manage courses',
            'review registrations', 'review results', 'view reports', 'manage academic catalog',
        ]);
        $lecturerRole->syncPermissions(['view assigned courses', 'submit results']);
        $studentRole->syncPermissions(['view own records', 'register courses']);

        $technology = Department::firstOrCreate(
            ['code' => 'TECH'],
            ['name' => 'Technology & Engineering'],
        );
        $business = Department::firstOrCreate(
            ['code' => 'BUS'],
            ['name' => 'Business Administration'],
        );
        $faculty = Faculty::firstOrCreate(
            ['code' => 'FTE'],
            ['name' => 'Faculty of Technology & Engineering', 'active' => true],
        );
        $technology->update(['faculty_id' => $faculty->id]);

        $softwareProgram = AcademicProgram::firstOrCreate(
            ['department_id' => $technology->id, 'code' => 'BSE'],
            ['name' => 'Software Engineering', 'active' => true],
        );
        $accountingProgram = AcademicProgram::firstOrCreate(
            ['department_id' => $business->id, 'code' => 'BACC'],
            ['name' => 'Accounting', 'active' => true],
        );
        $technologyLevel = AcademicLevel::firstOrCreate(
            ['department_id' => $technology->id, 'name' => '200'],
            ['sort_order' => 200, 'active' => true],
        );
        $businessLevel = AcademicLevel::firstOrCreate(
            ['department_id' => $business->id, 'name' => '100'],
            ['sort_order' => 100, 'active' => true],
        );
        $softwareProgram->levels()->syncWithoutDetaching([$technologyLevel->id]);
        $accountingProgram->levels()->syncWithoutDetaching([$businessLevel->id]);

        $session = AcademicSession::firstOrCreate(
            ['name' => '2026/2027'],
            ['starts_at' => '2026-09-01', 'ends_at' => '2027-07-31', 'active' => true],
        );
        if (! AcademicSession::where('active', true)->exists()) {
            $session->update(['active' => true]);
        }
        $semester = Semester::firstOrCreate(
            ['academic_session_id' => $session->id, 'name' => 'First'],
            ['starts_at' => '2026-09-01', 'ends_at' => '2026-12-20', 'active' => true],
        );

        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@uemp.test'],
            ['name' => 'University Super Admin', 'password' => 'password', 'department_id' => null],
        );
        $admin = User::updateOrCreate(
            ['email' => 'admin@uemp.test'],
            ['name' => 'Kwame Mensah', 'password' => 'password', 'department_id' => $technology->id],
        );
        $lecturer = User::updateOrCreate(
            ['email' => 'lecturer@uemp.test'],
            ['name' => 'Ama Mensah', 'password' => 'password', 'department_id' => $technology->id],
        );
        $student = User::updateOrCreate(
            ['email' => 'student@uemp.test'],
            [
                'name' => 'Kojo Asante',
                'password' => 'password',
                'department_id' => $technology->id,
                'student_number' => 'UEMP-2026-001',
                'faculty' => 'Technology & Engineering',
                'program' => 'Software Engineering',
                'academic_level' => '200',
                'academic_program_id' => $softwareProgram->id,
                'academic_level_id' => $technologyLevel->id,
            ],
        );
        $businessStudent = User::updateOrCreate(
            ['email' => 'business.student@uemp.test'],
            [
                'name' => 'Esi Owusu',
                'password' => 'password',
                'department_id' => $business->id,
                'student_number' => 'UEMP-2026-002',
                'faculty' => 'Business',
                'program' => 'Accounting',
                'academic_level' => '100',
                'academic_program_id' => $accountingProgram->id,
                'academic_level_id' => $businessLevel->id,
            ],
        );

        $superAdmin->syncRoles($superAdminRole);
        $admin->syncRoles($departmentAdminRole);
        $lecturer->syncRoles($lecturerRole);
        $student->syncRoles($studentRole);
        $businessStudent->syncRoles($studentRole);

        $course = Course::firstOrCreate(
            ['department_id' => $technology->id, 'code' => 'SWE201'],
            ['title' => 'Software Engineering', 'credit_units' => 3, 'active' => true],
        );
        $course->lecturers()->syncWithoutDetaching([$lecturer->id]);

        $offering = CourseOffering::firstOrCreate(
            [
                'course_id' => $course->id,
                'semester_id' => $semester->id,
                'academic_program_id' => $softwareProgram->id,
                'academic_level_id' => $technologyLevel->id,
            ],
            ['active' => true],
        );

        $registration = CourseRegistration::firstOrCreate(
            [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'academic_year' => '2026/2027',
                'semester' => 'First',
            ],
            ['status' => 'pending'],
        );
        if ($registration->course_offering_id !== $offering->id) {
            $registration->update(['course_offering_id' => $offering->id]);
        }

        Result::firstOrCreate(
            ['course_registration_id' => $registration->id],
            [
                'submitted_by' => $lecturer->id,
                'ca_score' => 26,
                'exam_score' => 45,
                'total_score' => 71,
                'grade' => 'B',
                'status' => 'submitted',
                'submitted_at' => now(),
            ],
        );
    }
}
