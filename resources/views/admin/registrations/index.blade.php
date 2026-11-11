@extends('layouts.app')

@section('title', 'Registrations')
@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Academic review</p>
            <h1>Course registrations</h1>
            <p class="muted">Review student requests for this department.</p>
        </div>
    </div>

    <section class="data-section">
        <div class="table-toolbar">
            <div><h2>Registration queue</h2><p>{{ $pendingCount }} awaiting decision</p></div>
            <form class="inline-form" method="GET" action="{{ route('admin.registrations.index') }}">
                <select name="status" aria-label="Registration status">
                    <option value="">All statuses</option>
                    @foreach (['pending', 'confirmed', 'rejected'] as $value)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ ucfirst($value) }}</option>@endforeach
                </select>
                <input name="academic_year" value="{{ $filters['academicYear'] }}" placeholder="Academic year" aria-label="Academic year">
                <select name="semester" aria-label="Semester">
                    <option value="">All semesters</option>
                    @foreach (['First', 'Second', 'Summer'] as $value)<option value="{{ $value }}" @selected($filters['semester'] === $value)>{{ $value }}</option>@endforeach
                </select>
                @if ($departments->isNotEmpty())
                    <select name="department_id" aria-label="Department">
                        <option value="">All departments</option>
                        @foreach ($departments as $department)<option value="{{ $department->id }}" @selected((string) $filters['departmentId'] === (string) $department->id)>{{ $department->name }}</option>@endforeach
                    </select>
                @endif
                <button class="button button-small" type="submit">Filter</button>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Student</th><th>Course</th><th>Academic year</th><th>Semester</th><th>Status</th><th>Decision</th></tr></thead>
                <tbody>
                    @forelse ($registrations as $registration)
                        <tr>
                            <td><strong>{{ $registration->student->name }}</strong><small>{{ $registration->student->student_number }}</small></td>
                            <td><strong>{{ $registration->course->code }}</strong><small>{{ $registration->course->title }}</small></td>
                            <td>{{ $registration->academic_year }}</td>
                            <td>{{ $registration->semester }}</td>
                            <td><span class="status status-{{ $registration->status }}">{{ ucfirst($registration->status) }}</span></td>
                            <td>
                                @if ($registration->status === 'pending')
                                    <div class="registration-decisions">
                                        <form method="POST" action="{{ route('admin.registrations.update', $registration) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="confirmed">
                                            <button class="button button-small" type="submit">Confirm</button>
                                        </form>
                                        <details class="registration-rejection">
                                            <summary class="button button-small button-quiet">Reject</summary>
                                            <form class="registration-rejection-form" method="POST" action="{{ route('admin.registrations.update', $registration) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="rejected">
                                                <label for="rejection-reason-{{ $registration->id }}">Reason for rejection</label>
                                                <textarea id="rejection-reason-{{ $registration->id }}" name="rejection_reason" rows="3" maxlength="2000" required placeholder="Explain why this request is rejected"></textarea>
                                                <button class="button button-small button-quiet" type="submit">Record rejection</button>
                                            </form>
                                        </details>
                                    </div>
                                @else
                                    <span class="muted">Decision recorded</span>
                                    @if ($registration->status === 'rejected' && $registration->rejection_reason)
                                        <small class="decision-reason"><strong>Reason:</strong> {{ $registration->rejection_reason }}</small>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="6">No course registrations to review.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $registrations->links() }}</div>
    </section>
@endsection
