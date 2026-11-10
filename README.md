# University Education Management Portal (UEMP)

Assessment project for Lead Web Praxis Media Limited.

| | |
| --- | --- |
| Position | Technology & Engineering Manager |
| Selected workflow | Administrator, with a Department Admin focus |
| Framework | Laravel 13.x |
| Runtime | PHP 8.4 |
| Database | PostgreSQL |
| Development approach | Test-driven development (TDD) |

## Overview

UEMP is a role-based university administration portal. This MVP prioritizes the Department Admin workflow while providing the supporting accounts and controls needed to exercise it end to end.

## Scope

### Department Admin

- Department Admin role and dashboard
- Student and lecturer management, limited to the administrator's department
- Lecturer course assignments and departmental course management
- Student course-registration review and confirmation
- Submitted-result review, approval, rejection, and return for correction
- Department-level academic and registration reports
- Role-based access control (RBAC) and department data isolation

### Supporting roles

- Super Admin (for initial setup)
- Student and Lecturer accounts for exercising the admin workflows

## Core workflows

1. Department administrators manage students, lecturers, and courses in their department.
2. Administrators review student course registrations and confirm eligible registrations.
3. Lecturers submit course results; Department Admins review and approve, reject, or return them for correction.
4. Administrators review departmental reports without accessing records outside their department.
5. Super Admins manage the university structure, departments, and administrator accounts.

## Tech Stack

- Laravel 13.x
- PHP 8.4
- PostgreSQL
- Spatie Laravel Permission for roles and permissions
- Tailwind CSS
- Pest for feature and unit tests (TDD)

## Grading scale

| Score | Grade | Grade points |
| --- | --- | ---: |
| 80-100 | A | 4.0 |
| 70-79 | B | 3.0 |
| 60-69 | C | 2.0 |
| 50-59 | D | 1.0 |
| 0-49 | F | 0.0 |

Cumulative GPA is credit-weighted and uses approved results only. The scale is a documented MVP default and can be changed to match university policy.

## Local setup

Requirements: PHP 8.4, Composer, Node.js with npm, and PostgreSQL.

1. Install PHP and JavaScript dependencies:

	```sh
	composer install
	npm install
	```

2. Create the local environment file and application key:

	```sh
	cp .env.example .env
	php artisan key:generate
	```

3. Set the PostgreSQL connection values in `.env`, then run migrations and seed the local development data:

	```sh
	php artisan migrate --seed
	```

4. Build frontend assets and start the application:

	```sh
	npm run build
	php artisan serve
	```

On Windows PowerShell, use `Copy-Item .env.example .env` in place of `cp`.

The seeded development accounts all use the password `password`:

| Role | Email |
| --- | --- |
| Super Admin | `superadmin@uemp.test` |
| Department Admin | `admin@uemp.test` |
| Lecturer | `lecturer@uemp.test` |
| Student | `student@uemp.test` |

These are local demonstration credentials only. Change or remove them before deploying the application anywhere public.

## Testing

Run the test suite with:

```sh
php artisan test
```

Tests should cover authorization boundaries, department scoping, registration rules, and the result approval workflow. Use a dedicated test database configured in `phpunit.xml` or the test environment; do not point automated tests at development or production data.

## Assessment deliverables

- Working web application and role-specific dashboards
- Database schema and migrations
- Seeded local test accounts for each workflow role
- Automated tests and setup instructions
- Technical documentation for the architecture and key decisions
