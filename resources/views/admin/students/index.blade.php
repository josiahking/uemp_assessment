@extends('layouts.app')

@section('title', 'Students')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">People / Department</p><h1>Students</h1><p class="muted">Manage student records in your department.</p></div><a class="button button-primary" href="#new-student">Add student</a></div>
    <section class="data-section">
        <div class="table-toolbar"><div><h2>Student directory</h2><p>{{ $students->count() }} records</p></div><input class="search-input" type="search" placeholder="Search students" aria-label="Search students" data-table-search></div>
        <div class="table-wrap"><table><thead><tr><th>Student</th><th>Student number</th><th>Programme</th><th>Level</th><th>Department</th></tr></thead><tbody>
            @forelse ($students as $student)<tr data-searchable><td><strong>{{ $student->name }}</strong><small>{{ $student->email }}</small></td><td>{{ $student->student_number ?? 'Not assigned' }}</td><td>{{ $student->program ?? 'Not assigned' }}</td><td>{{ $student->academic_level ?? 'Not assigned' }}</td><td>{{ $student->department?->name ?? 'Unassigned' }}</td></tr>@empty<tr><td class="empty-state" colspan="5">No students found for this department.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <section class="form-section" id="new-student"><div class="section-heading"><div><p class="eyebrow">New record</p><h2>Add a student</h2></div></div>
        <form class="form-grid" method="POST" action="{{ route('admin.students.store') }}">
            @csrf
            @if ($departments->isNotEmpty())<label>Department<select name="department_id" required><option value="">Choose department</option>@foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></label>@endif
            <label>Full name<input name="name" value="{{ old('name') }}" required></label><label>Email address<input name="email" type="email" value="{{ old('email') }}" required></label><label>Temporary password<input name="password" type="password" minlength="8" required></label><label>Student number<input name="student_number" value="{{ old('student_number') }}" required></label><label>Faculty<input name="faculty" value="{{ old('faculty') }}"></label><label>Programme<input name="program" value="{{ old('program') }}" required></label><label>Academic level<input name="academic_level" value="{{ old('academic_level') }}" placeholder="e.g. 200" required></label>
            <div class="form-actions"><button class="button button-primary" type="submit">Create student</button></div>
        </form>
    </section>
@endsection