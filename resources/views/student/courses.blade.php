@extends('layouts.app')

@section('title', 'Course registration')
@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Student workspace</p>
            <h1>Course registration</h1>
            <p class="muted">Choose courses offered by your department for the semester.</p>
        </div>
    </div>

    <section class="data-section">
        <div class="table-toolbar">
            <div><h2>Available courses</h2><p>{{ $offerings->count() }} eligible offerings</p></div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Course</th><th>Credits</th><th>Lecturer</th><th>Session / semester</th><th>Eligibility</th><th>Registration</th></tr></thead>
                <tbody>
                    @forelse ($offerings as $offering)
                        <tr>
                            <td><strong>{{ $offering->course->code }}</strong><small>{{ $offering->course->title }}</small></td>
                            <td>{{ $offering->course->credit_units }}</td>
                            <td>{{ $offering->course->lecturers->pluck('name')->join(', ') ?: 'To be assigned' }}</td>
                            <td>{{ $offering->semester->academicSession->name }}<small>{{ $offering->semester->name }}</small></td>
                            <td>{{ $offering->program?->name ?? 'All programs' }}<small>{{ $offering->academicLevel?->name ?? 'All levels' }}</small></td>
                            <td><form method="POST" action="{{ route('student.registrations.store', $offering) }}">@csrf<button class="button button-small" type="submit">Register</button></form></td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="6">No active offerings match your program and level.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading"><div><p class="eyebrow">Your activity</p><h2>Registration history</h2></div></div>
        <div class="action-list">
            @forelse ($registrations as $registration)
                <div class="action-row">
                    <span>
                        <strong>{{ $registration->course->code }} &middot; {{ $registration->course->title }}</strong>
                        <small>{{ $registration->academic_year }} &middot; {{ $registration->semester }} @if ($registration->result?->status === 'approved') &middot; Result: {{ $registration->result->grade }} ({{ $registration->result->total_score }})@endif</small>
                        @if ($registration->status === 'rejected' && $registration->rejection_reason)
                            <small class="decision-reason">Reason for rejection: {{ $registration->rejection_reason }}</small>
                        @endif
                    </span>
                    <span class="status status-{{ $registration->result?->status === 'approved' ? 'approved' : $registration->status }}">{{ $registration->result?->status === 'approved' ? 'Result available' : ucfirst($registration->status) }}</span>
                </div>
            @empty
                <p class="muted">You have not submitted any course registrations yet.</p>
            @endforelse
        </div>
    </section>
@endsection
