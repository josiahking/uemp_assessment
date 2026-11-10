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
use App\Models\ResultEvent;
use App\Models\Semester;
use App\Models\SystemActivity;
use App\Models\User;
use App\Services\StudentNumberGenerator;
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
        $businessFaculty = Faculty::firstOrCreate(
            ['code' => 'FBA'],
            ['name' => 'Faculty of Business & Administration', 'active' => true],
        );
        $business->update(['faculty_id' => $businessFaculty->id]);

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
        $technologyLevels = collect(['100', '200', '300', '400'])->map(fn ($name) => AcademicLevel::firstOrCreate(
            ['department_id' => $technology->id, 'name' => $name],
            ['sort_order' => (int) $name, 'active' => true],
        ));
        $businessLevels = collect(['100', '200', '300', '400'])->map(fn ($name) => AcademicLevel::firstOrCreate(
            ['department_id' => $business->id, 'name' => $name],
            ['sort_order' => (int) $name, 'active' => true],
        ));
        $computerScienceProgram = AcademicProgram::firstOrCreate(
            ['department_id' => $technology->id, 'code' => 'BCS'],
            ['name' => 'Computer Science', 'active' => true],
        );
        $businessManagementProgram = AcademicProgram::firstOrCreate(
            ['department_id' => $business->id, 'code' => 'BBM'],
            ['name' => 'Business Management', 'active' => true],
        );
        foreach ([$softwareProgram, $computerScienceProgram] as $program) {
            $program->levels()->syncWithoutDetaching($technologyLevels->pluck('id')->all());
        }
        foreach ([$accountingProgram, $businessManagementProgram] as $program) {
            $program->levels()->syncWithoutDetaching($businessLevels->pluck('id')->all());
        }

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
        $secondSemester = Semester::firstOrCreate(
            ['academic_session_id' => $session->id, 'name' => 'Second'],
            ['starts_at' => '2027-01-10', 'ends_at' => '2027-05-30', 'active' => true],
        );
        $nextSession = AcademicSession::firstOrCreate(
            ['name' => '2027/2028'],
            ['starts_at' => '2027-09-01', 'ends_at' => '2028-07-31', 'active' => false],
        );
        Semester::firstOrCreate(
            ['academic_session_id' => $nextSession->id, 'name' => 'First'],
            ['starts_at' => '2027-09-01', 'ends_at' => '2027-12-20', 'active' => false],
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
                'student_number' => 'UEMP-2026-000001',
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
                'student_number' => 'UEMP-2026-000002',
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

        $this->seedScenarioData(
            $technology,
            $business,
            $softwareProgram,
            $accountingProgram,
            $computerScienceProgram,
            $businessManagementProgram,
            $technologyLevels,
            $businessLevels,
            $session,
            $semester,
            $secondSemester,
            $admin,
            $lecturer,
            $student,
            $businessStudent,
            $course,
            app(StudentNumberGenerator::class),
        );
    }

    private function seedScenarioData(
        Department $technology,
        Department $business,
        AcademicProgram $softwareProgram,
        AcademicProgram $accountingProgram,
        AcademicProgram $computerScienceProgram,
        AcademicProgram $businessManagementProgram,
        $technologyLevels,
        $businessLevels,
        AcademicSession $session,
        Semester $firstSemester,
        Semester $secondSemester,
        User $admin,
        User $firstLecturer,
        User $firstStudent,
        User $businessStudent,
        Course $firstCourse,
        StudentNumberGenerator $studentNumbers,
    ): void {
        $lecturerRole = Role::findOrCreate('Lecturer', 'web');
        $studentRole = Role::findOrCreate('Student', 'web');
        $lecturersByDepartment = [
            $technology->id => [$firstLecturer],
            $business->id => [],
        ];

        for ($index = 2; $index <= 20; $index++) {
            $department = $index <= 10 ? $technology : $business;
            $email = sprintf('lecturer.%02d@uemp.test', $index);
            $lecturer = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => fake()->name(),
                    'password' => 'password',
                    'department_id' => $department->id,
                    'faculty' => $department->faculty?->name,
                    'account_status' => 'active',
                ],
            );
            $lecturer->syncRoles($lecturerRole);
            $lecturersByDepartment[$department->id][] = $lecturer;
        }

        $studentsByDepartment = [
            $technology->id => [$firstStudent],
            $business->id => [$businessStudent],
        ];
        $programsByDepartment = [
            $technology->id => [$softwareProgram, $computerScienceProgram],
            $business->id => [$accountingProgram, $businessManagementProgram],
        ];
        $levelsByDepartment = [
            $technology->id => $technologyLevels->values(),
            $business->id => $businessLevels->values(),
        ];

        for ($index = 3; $index <= 20; $index++) {
            $department = count($studentsByDepartment[$technology->id]) < 10 ? $technology : $business;
            $programs = $programsByDepartment[$department->id];
            $levels = $levelsByDepartment[$department->id];
            $program = $programs[($index - 3) % count($programs)];
            $level = $levels[($index - 3) % $levels->count()];
            $email = sprintf('student.%02d@uemp.test', $index);
            $existing = User::where('email', $email)->first();
            $student = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => fake()->name(),
                    'password' => 'password',
                    'department_id' => $department->id,
                    'student_number' => $existing?->student_number ?? $studentNumbers->next(),
                    'faculty' => $department->faculty?->name,
                    'program' => $program->name,
                    'academic_level' => $level->name,
                    'academic_program_id' => $program->id,
                    'academic_level_id' => $level->id,
                    'account_status' => 'active',
                ],
            );
            $student->syncRoles($studentRole);
            $studentsByDepartment[$department->id][] = $student;
        }

        $coursesByDepartment = [
            $technology->id => [$firstCourse],
            $business->id => [],
        ];
        for ($index = 2; $index <= 20; $index++) {
            $department = $index <= 10 ? $technology : $business;
            $localIndex = $department->is($technology) ? $index : $index - 10;
            $code = $department->is($technology)
                ? 'SWE'.(200 + $localIndex)
                : 'BUS'.(100 + $localIndex);
            $course = Course::updateOrCreate(
                ['department_id' => $department->id, 'code' => $code],
                [
                    'title' => fake()->unique()->words(3, true),
                    'credit_units' => 2 + ($localIndex % 3),
                    'active' => true,
                ],
            );
            $lecturers = $lecturersByDepartment[$department->id];
            $lecturer = $lecturers[($localIndex - 1) % count($lecturers)];
            $course->lecturers()->syncWithoutDetaching([$lecturer->id]);
            $coursesByDepartment[$department->id][] = $course;
        }

        $programForCourse = [
            $technology->id => [$softwareProgram, $computerScienceProgram],
            $business->id => [$accountingProgram, $businessManagementProgram],
        ];
        $results = [];
        $registrationStatuses = [
            'pending', 'confirmed', 'confirmed', 'rejected', 'pending',
            'confirmed', 'confirmed', 'pending', 'confirmed', 'confirmed',
            'rejected', 'pending', 'confirmed', 'confirmed', 'pending',
            'confirmed', 'rejected', 'confirmed', 'pending', 'confirmed',
        ];
        $resultStatuses = [
            2 => 'submitted', 3 => 'approved', 6 => 'returned', 7 => 'rejected',
            9 => 'submitted', 10 => 'approved', 13 => 'returned', 14 => 'approved',
            16 => 'rejected', 18 => 'submitted', 20 => 'approved',
        ];
        $registrationIndex = 0;

        foreach ([$technology, $business] as $department) {
            foreach ($coursesByDepartment[$department->id] as $courseIndex => $course) {
                $registrationIndex++;
                $student = $studentsByDepartment[$department->id][$courseIndex];
                $program = AcademicProgram::findOrFail($student->academic_program_id);
                $level = AcademicLevel::findOrFail($student->academic_level_id);
                $semester = $courseIndex % 2 === 0 ? $firstSemester : $secondSemester;
                $offering = CourseOffering::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'semester_id' => $semester->id,
                        'academic_program_id' => $program->id,
                        'academic_level_id' => $level->id,
                    ],
                    ['active' => true],
                );
                $status = $registrationStatuses[$registrationIndex - 1];
                $registration = CourseRegistration::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'academic_year' => $session->name,
                        'semester' => $semester->name,
                    ],
                    [
                        'course_offering_id' => $offering->id,
                        'status' => $status,
                    ],
                );

                if ($registrationIndex <= 9) {
                    SystemActivity::firstOrCreate(
                        ['action' => 'course_registration.'.$status, 'subject_type' => CourseRegistration::class, 'subject_id' => $registration->id],
                        ['actor_id' => $admin->id, 'details' => ['seeded' => true], 'created_at' => now()->subMinutes(30 - $registrationIndex)],
                    );
                }

                if (! isset($resultStatuses[$registrationIndex])) {
                    continue;
                }

                $lecturer = $course->lecturers()->firstOrFail();
                $resultStatus = $resultStatuses[$registrationIndex];
                $caScore = 20 + ($registrationIndex % 15);
                $examScore = 40 + ($registrationIndex % 15);
                $totalScore = $caScore + $examScore;
                $grade = match (true) {
                    $totalScore >= 80 => 'A',
                    $totalScore >= 70 => 'B',
                    $totalScore >= 60 => 'C',
                    $totalScore >= 50 => 'D',
                    default => 'F',
                };
                $result = Result::updateOrCreate(
                    ['course_registration_id' => $registration->id],
                    [
                        'submitted_by' => $lecturer->id,
                        'reviewed_by' => $resultStatus === 'submitted' ? null : $admin->id,
                        'ca_score' => $caScore,
                        'exam_score' => $examScore,
                        'total_score' => $totalScore,
                        'grade' => $grade,
                        'status' => $resultStatus,
                        'review_note' => in_array($resultStatus, ['returned', 'rejected'], true) ? 'Please review the recorded scores.' : null,
                        'submitted_at' => now()->subDays($registrationIndex),
                        'reviewed_at' => $resultStatus === 'submitted' ? null : now()->subDays($registrationIndex - 1),
                    ],
                );
                ResultEvent::firstOrCreate(
                    ['result_id' => $result->id, 'event' => $resultStatus, 'to_status' => $resultStatus],
                    [
                        'actor_id' => $resultStatus === 'submitted' ? $lecturer->id : $admin->id,
                        'from_status' => $resultStatus === 'submitted' ? 'draft' : 'submitted',
                        'metadata' => ['seeded' => true, 'ca_score' => $caScore, 'exam_score' => $examScore],
                        'created_at' => now()->subDays($registrationIndex - 1),
                    ],
                );
                SystemActivity::firstOrCreate(
                    ['action' => 'result.'.$resultStatus, 'subject_type' => Result::class, 'subject_id' => $result->id],
                    [
                        'actor_id' => $resultStatus === 'submitted' ? $lecturer->id : $admin->id,
                        'details' => ['seeded' => true, 'grade' => $grade],
                        'created_at' => now()->subDays($registrationIndex - 1),
                    ],
                );
                $results[] = $result;
            }
        }
    }
}
