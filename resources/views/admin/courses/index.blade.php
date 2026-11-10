@extends('layouts.app')

@section('title', 'Courses')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Academic setup</p><h1>Courses</h1><p class="muted">Create courses and assign lecturers in your department.</p></div></div>
    <section class="data-section"><div class="table-toolbar"><div><h2>Course catalogue</h2><p>{{ $courses->count() }} courses</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Course</th><th>Credits</th><th>Department</th><th>Lecturers</th><th>Assign lecturer</th></tr></thead><tbody>
            @forelse ($courses as $course)<tr><td><strong>{{ $course->code }}</strong><small>{{ $course->title }}</small></td><td>{{ $course->credit_units }}</td><td>{{ $course->department->name }}</td><td>{{ $course->lecturers->pluck('name')->join(', ') ?: 'Unassigned' }}</td><td><form class="inline-form" method="POST" action="{{ route('admin.courses.assign-lecturer', $course) }}">@csrf<select name="lecturer_id" aria-label="Select lecturer for {{ $course->code }}" required><option value="">Select</option>@foreach ($lecturers->where('department_id', $course->department_id) as $lecturer)<option value="{{ $lecturer->id }}">{{ $lecturer->name }}</option>@endforeach</select><button class="button button-small" type="submit">Assign</button></form></td></tr>@empty<tr><td class="empty-state" colspan="5">No courses have been created yet.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <section class="form-section"><div class="section-heading"><div><p class="eyebrow">Academic setup</p><h2>Create a course</h2></div></div>
        <form class="form-grid" method="POST" action="{{ route('admin.courses.store') }}">
            @csrf
            @if ($departments->isNotEmpty())<label>Department<select name="department_id" required><option value="">Choose department</option>@foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></label>@endif
            <label>Course code<input name="code" value="{{ old('code') }}" required></label><label>Course title<input name="title" value="{{ old('title') }}" required></label><label>Credit units<input name="credit_units" type="number" min="1" max="10" value="3" required></label>
            <div class="form-actions"><button class="button button-primary" type="submit">Create course</button></div>
        </form>
    </section>
@endsection