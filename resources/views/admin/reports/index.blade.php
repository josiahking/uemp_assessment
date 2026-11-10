@extends('layouts.app')

@section('title', 'Department reports')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Department / Reporting</p><h1>Academic reports</h1><p class="muted">A current summary for {{ $departmentName }}.</p></div></div>
    <section class="metric-grid" aria-label="Department report summary"><article class="metric"><span>Students</span><strong>{{ $students }}</strong><small>Active student accounts</small></article><article class="metric"><span>Lecturers</span><strong>{{ $lecturers }}</strong><small>Teaching staff</small></article><article class="metric"><span>Courses</span><strong>{{ $courses }}</strong><small>Department catalogue</small></article><article class="metric metric-accent"><span>Pending registrations</span><strong>{{ $pendingRegistrations }}</strong><small>Awaiting confirmation</small></article><article class="metric"><span>Approved results</span><strong>{{ $approvedResults }}</strong><small>Ready for students</small></article></section>
@endsection