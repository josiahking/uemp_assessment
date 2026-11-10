@extends('layouts.app')

@section('title', 'Course registration')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Student workspace</p><h1>Course registration</h1><p class="muted">Choose courses offered by your department for the semester.</p></div></div>
    <section class="data-section"><div class="table-toolbar"><div><h2>Available courses</h2><p>{{ $courses->count() }} courses in your department</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Course</th><th>Credits</th><th>Lecturer</th><th>Semester registration</th></tr></thead><tbody>
            @forelse ($courses as $course)<tr><td><strong>{{ $course->code }}</strong><small>{{ $course->title }}</small></td><td>{{ $course->credit_units }}</td><td>{{ $course->lecturers->pluck('name')->join(', ') ?: 'To be assigned' }}</td><td><form class="inline-form" method="POST" action="{{ route('student.registrations.store', $course) }}">@csrf<label class="visually-hidden" for="year-{{ $course->id }}">Academic year</label><input id="year-{{ $course->id }}" name="academic_year" value="2026/2027" required><select name="semester" aria-label="Semester for {{ $course->code }}"><option>First</option><option>Second</option><option>Summer</option></select><button class="button button-small" type="submit">Register</button></form></td></tr>@empty<tr><td class="empty-state" colspan="4">No active courses are available for your department.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <section class="section-block"><div class="section-heading"><div><p class="eyebrow">Your activity</p><h2>Registration history</h2></div></div><div class="action-list">
        @forelse ($registrations as $registration)<div class="action-row"><span><strong>{{ $registration->course->code }} · {{ $registration->course->title }}</strong><small>{{ $registration->academic_year }} · {{ $registration->semester }} @if ($registration->result?->status === 'approved') · Result: {{ $registration->result->grade }} ({{ $registration->result->total_score }})@endif</small></span><span class="status status-{{ $registration->result?->status === 'approved' ? 'approved' : $registration->status }}">{{ $registration->result?->status === 'approved' ? 'Result available' : ucfirst($registration->status) }}</span></div>@empty<p class="muted">You have not submitted any course registrations yet.</p>@endforelse
    </div></section>
@endsection