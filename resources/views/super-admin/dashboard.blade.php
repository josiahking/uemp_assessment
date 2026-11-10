@extends('layouts.app')

@section('title', 'University dashboard')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">University-wide overview</p><h1>Super Admin dashboard</h1><p class="muted">A live view of academic operations across the university.</p></div><a class="button button-primary" href="{{ route('super-admin.academics.index') }}">Academic setup</a></div>
    <section class="metric-grid" aria-label="University summary">
        <article class="metric"><span>Faculties</span><strong>{{ $faculties }}</strong><small>University structure</small></article>
        <article class="metric"><span>Departments</span><strong>{{ $departments }}</strong><small>Across all faculties</small></article>
        <article class="metric"><span>Students</span><strong>{{ $students }}</strong><small>Student accounts</small></article>
        <article class="metric"><span>Lecturers</span><strong>{{ $lecturers }}</strong><small>Lecturer accounts</small></article>
        <article class="metric"><span>Courses</span><strong>{{ $courses }}</strong><small>Academic catalogue</small></article>
        <article class="metric"><span>Active sessions</span><strong>{{ $activeSessions }}</strong><small>Current academic session</small></article>
        <article class="metric metric-accent"><span>Pending registrations</span><strong>{{ $pendingRegistrations }}</strong><small>Across all departments</small></article>
        <article class="metric metric-accent"><span>Results awaiting review</span><strong>{{ $submittedResults }}</strong><small>Department approval queue</small></article>
    </section>
    <section class="data-section"><div class="table-toolbar"><div><h2>Recent administrative activity</h2><p>Latest account and academic structure changes</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Action</th><th>Performed by</th><th>Record</th><th>Time</th></tr></thead><tbody>@forelse ($activities as $activity)<tr><td><strong>{{ str($activity->action)->replace('.', ' ')->title() }}</strong></td><td>{{ $activity->actor?->name ?? 'Former account' }}</td><td>{{ class_basename($activity->subject_type ?? '') }} #{{ $activity->subject_id ?? '—' }}</td><td>{{ $activity->created_at->diffForHumans() }}</td></tr>@empty<tr><td class="empty-state" colspan="4">No administrative activity recorded yet.</td></tr>@endforelse</tbody></table></div>
    </section>
@endsection