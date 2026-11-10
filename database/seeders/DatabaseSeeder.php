<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Result;
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
            'review registrations', 'review results', 'view reports',
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

        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@uemp.test'],
            ['name' => 'University Super Admin', 'password' => 'password', 'department_id' => null],
        );
        $admin = User::updateOrCreate(
            ['email' => 'admin@uemp.test'],
            ['name' => 'Technology Department Admin', 'password' => 'password', 'department_id' => $technology->id],
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

        $registration = CourseRegistration::firstOrCreate(
            [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'academic_year' => '2026/2027',
                'semester' => 'First',
            ],
            ['status' => 'pending'],
        );

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
