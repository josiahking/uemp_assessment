@extends('layouts.app')

@section('title', 'My courses')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Lecturer workspace</p><h1>My courses</h1><p class="muted">View confirmed registrations and submit course results.</p></div></div>
    <section class="data-section"><div class="table-toolbar"><div><h2>Assigned courses</h2><p>{{ $courses->count() }} courses</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Course</th><th>Department</th><th>Credits</th></tr></thead><tbody>@forelse ($courses as $course)<tr><td><strong>{{ $course->code }}</strong><small>{{ $course->title }}</small></td><td>{{ $course->department->name }}</td><td>{{ $course->credit_units }}</td></tr>@empty<tr><td class="empty-state" colspan="3">You have not been assigned any courses.</td></tr>@endforelse</tbody></table></div>
    </section>
    <section class="data-section"><div class="table-toolbar"><div><h2>Confirmed registrations</h2><p>{{ $registrations->count() }} students</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Student</th><th>Course</th><th>Semester</th><th>Result</th></tr></thead><tbody>
            @forelse ($registrations as $registration)<tr><td><strong>{{ $registration->student->name }}</strong><small>{{ $registration->student->student_number }}</small></td><td>{{ $registration->course->code }}</td><td>{{ $registration->academic_year }} · {{ $registration->semester }}</td><td>@if ($registration->result && ! in_array($registration->result->status, ['draft', 'returned'], true))<span class="status status-{{ $registration->result->status }}">{{ ucfirst($registration->result->status) }}</span>@else<form class="inline-form" method="POST" action="{{ route('lecturer.results.store', $registration) }}">@csrf<input type="number" name="ca_score" min="0" max="40" step="0.01" placeholder="CA / 40" value="{{ $registration->result?->ca_score }}" required><input type="number" name="exam_score" min="0" max="60" step="0.01" placeholder="Exam / 60" value="{{ $registration->result?->exam_score }}" required><button class="button button-small" type="submit">{{ $registration->result ? 'Resubmit' : 'Submit result' }}</button></form>@endif</td></tr>@empty<tr><td class="empty-state" colspan="4">No confirmed registrations for your courses.</td></tr>@endforelse
        </tbody></table></div>
    </section>
@endsection