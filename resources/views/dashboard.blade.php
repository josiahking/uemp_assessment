@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">{{ now()->format('l, j F Y') }}</p>
            <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}</h1>
            <p class="muted">The latest activity across {{ auth()->user()->department?->name ?? 'your university' }}.</p>
        </div>
        @if (auth()->user()->hasAnyRole(['Department Admin', 'Super Admin']))
            <a class="button button-primary" href="{{ route('admin.students.index') }}">Manage department</a>
        @elseif (auth()->user()->hasRole('Lecturer'))
            <a class="button button-primary" href="{{ route('lecturer.courses.index') }}">Open my courses</a>
        @else
            <a class="button button-primary" href="{{ route('student.courses.index') }}">Register for courses</a>
        @endif
    </div>

    @if (auth()->user()->hasAnyRole(['Department Admin', 'Super Admin']))
        <section class="metric-grid" aria-label="Department summary">
            <article class="metric"><span>Students</span><strong>{{ $students }}</strong><small>In your department</small></article>
            <article class="metric"><span>Lecturers</span><strong>{{ $lecturers }}</strong><small>Teaching staff</small></article>
            <article class="metric"><span>Courses</span><strong>{{ $courses }}</strong><small>Available this year</small></article>
            <article class="metric metric-accent"><span>Needs review</span><strong>{{ $registrations + $results }}</strong><small>{{ $registrations }} registrations · {{ $results }} results</small></article>
        </section>
        <section class="section-block">
            <div class="section-heading"><div><p class="eyebrow">Department workflow</p><h2>Review queue</h2></div></div>
            <div class="action-list">
                <a href="{{ route('admin.registrations.index') }}"><span><strong>Course registrations</strong><small>Confirm or reject student requests</small></span><b>{{ $registrations }}</b></a>
                <a href="{{ route('admin.results.index') }}"><span><strong>Submitted results</strong><small>Approve, reject, or return for correction</small></span><b>{{ $results }}</b></a>
            </div>
        </section>
    @elseif (auth()->user()->hasRole('Lecturer'))
        <section class="metric-grid metric-grid-three"><article class="metric"><span>Assigned courses</span><strong>{{ $courses }}</strong><small>Current teaching load</small></article><article class="metric"><span>Registered students</span><strong>{{ $registrations }}</strong><small>Across assigned courses</small></article><article class="metric metric-accent"><span>Results to complete</span><strong>{{ $results }}</strong><small>Drafts and returned results</small></article></section>
        <section class="section-block"><div class="section-heading"><div><p class="eyebrow">Teaching</p><h2>Continue where you left off</h2></div></div><a class="text-link" href="{{ route('lecturer.courses.index') }}">View assigned courses <span aria-hidden="true">&rarr;</span></a></section>
    @else
        <section class="metric-grid metric-grid-three"><article class="metric"><span>Course registrations</span><strong>{{ $registrations }}</strong><small>All semesters</small></article><article class="metric"><span>Available courses</span><strong>{{ $courses }}</strong><small>In your department</small></article><article class="metric metric-accent"><span>Cumulative GPA</span><strong>{{ number_format($gpa, 2) }}</strong><small>Based on approved results</small></article></section>
        <section class="section-block"><div class="section-heading"><div><p class="eyebrow">Your academics</p><h2>Plan your semester</h2></div></div><a class="text-link" href="{{ route('student.courses.index') }}">Browse and register for courses <span aria-hidden="true">&rarr;</span></a></section>
    @endif
@endsection