# University Education Management Portal (UEMP)

**A role-based university administration MVP built with Laravel, focused on department-level data isolation, academic workflows, auditable decisions, and test-driven development.**

UEMP demonstrates how to design and implement a multi-role academic administration system with clear authorization boundaries. The primary workflow is **Department Admin**; supporting Super Admin, Lecturer, and Student roles make it possible to exercise registration, teaching, results, and approval processes end to end.

**[Watch the application demo](https://youtu.be/seueugivdvY)** · [Architecture](#architecture) · [Engineering decisions](#engineering-decisions) · [Run with Docker](#docker-setup) · [Testing](#testing)

> **Project scope:** This is a technical-assessment MVP, not a production university deployment. The repository includes development seed data, automated tests, and setup instructions. Reporting is currently in-portal rather than exported to files.

## Engineering highlights

- **Role-based access and department isolation:** Uses roles and permissions, plus department-scoped record lookups and mutation boundaries, to prevent administrators from accessing another department's records.
- **Academic eligibility rules:** Validates course registration against active offerings, sessions, semesters, programs, and levels on the server rather than trusting client-supplied academic context.
- **Auditable result approvals:** Supports lecturer result submission and Department Admin approval, rejection, or return for correction, with result events and administrative activity records.
- **Clear application boundaries:** Keeps HTTP validation and authorization in controllers while workflow services coordinate business operations, transactions, and activity logging.
- **Test-driven verification:** Includes feature and unit tests for authorization, academic workflows, grading, registration, and results, with isolated SQLite and PostgreSQL testing options.
- **Reproducible development:** Documents Docker Compose setup, seeded demonstration accounts, local setup, and testing commands.

## What the application supports

| Role | Main capabilities |
| --- | --- |
| **Department Admin** | Manage department students, lecturers, courses and allocations; review registrations and results; view departmental reports. |
| **Super Admin** | Configure academic structures, sessions, accounts, roles and permissions; monitor university-wide administrative activity. |
| **Lecturer** | Submit and correct results for assigned courses. |
| **Student** | Register for eligible active course offerings. |

## Key workflows

1. A Super Admin configures faculties, departments, programs, academic levels, sessions, semesters, and access permissions.
2. Department Admins manage departmental accounts, courses, lecturer allocations, and course offerings.
3. Students register for eligible offerings; administrators review registration requests.
4. Lecturers submit results; Department Admins approve, reject, or return results for correction.
5. Department and university administrators review reports and recorded activity within their authorized scope.

## Technology

**PHP 8.4 · Laravel 13 · PostgreSQL · Blade · Tailwind CSS · Spatie Laravel Permission · Pest · Docker Compose**

The MVP uses a documented A–F, 4.0-point grading assumption and calculates cumulative GPA from approved results only. See the detailed architecture, engineering decisions, setup, and tests below.
