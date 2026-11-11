@extends('layouts.app')

@section('title', 'Courses')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Academic setup</p><h1>Courses</h1><p class="muted">Create courses and assign lecturers in your department.</p></div></div>
    <section class="data-section"><div class="table-toolbar"><div><h2>Course catalogue</h2><p>{{ $courses->count() }} courses</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Course</th><th>Credits</th><th>Department</th><th>Lecturers</th><th>Course settings</th></tr></thead><tbody>
            @forelse ($courses as $course)
                <tr>
                    <td><strong>{{ $course->code }}</strong><small>{{ $course->title }}</small></td>
                    <td>{{ $course->credit_units }}</td>
                    <td>{{ $course->department->name }}</td>
                    <td>
                        {{ $course->lecturers->pluck('name')->join(', ') ?: 'Unassigned' }}
                        @foreach ($course->lecturers as $lecturer)
                            <form method="POST" action="{{ route('admin.courses.unassign-lecturer', [$course, $lecturer]) }}">
                                @csrf @method('DELETE')
                                <button class="text-button" type="submit">Remove {{ $lecturer->name }}</button>
                            </form>
                        @endforeach
                    </td>
                    <td>
                        <details>
                            <summary>Course settings</summary>
                            <form class="stacked-inline-form" method="POST" action="{{ route('admin.courses.update', $course) }}">
                                @csrf @method('PATCH')
                                <input name="code" aria-label="Course code" value="{{ $course->code }}" required>
                                <input name="title" aria-label="Course title" value="{{ $course->title }}" required>
                                <input name="credit_units" type="number" min="1" max="10" aria-label="Credit units" value="{{ $course->credit_units }}" required>
                                <select name="active" aria-label="Course status">
                                    <option value="1" @selected($course->active)>Active</option>
                                    <option value="0" @selected(! $course->active)>Inactive</option>
                                </select>
                                <button class="button button-small" type="submit">Save</button>
                            </form>
                            <form class="inline-form" method="POST" action="{{ route('admin.courses.assign-lecturer', $course) }}">
                                @csrf
                                <select name="lecturer_id" aria-label="Select lecturer for {{ $course->code }}" required>
                                    <option value="">Assign lecturer</option>
                                    @foreach ($lecturers->where('department_id', $course->department_id) as $lecturer)<option value="{{ $lecturer->id }}">{{ $lecturer->name }}</option>@endforeach
                                </select>
                                <button class="button button-small" type="submit">Assign</button>
                            </form>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td class="empty-state" colspan="5">No courses have been created yet.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
    <section class="data-section"><div class="table-toolbar"><div><h2>Course offerings</h2><p>{{ $offerings->count() }} program / level allocations</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Course</th><th>Session / semester</th><th>Program</th><th>Level</th><th>Status</th><th>Update</th></tr></thead><tbody>
            @forelse ($offerings as $offering)<tr><td><strong>{{ $offering->course->code }}</strong><small>{{ $offering->course->department->name }}</small></td><td>{{ $offering->semester->academicSession->name }}<small>{{ $offering->semester->name }}</small></td><td>{{ $offering->program?->name ?? 'All programs' }}</td><td>{{ $offering->academicLevel?->name ?? 'All levels' }}</td><td>{{ $offering->active ? 'Active' : 'Inactive' }}</td><td><form method="POST" action="{{ route('admin.offerings.update', $offering) }}">@csrf @method('PATCH')<input type="hidden" name="active" value="{{ $offering->active ? 0 : 1 }}"><button class="button button-small" type="submit">{{ $offering->active ? 'Deactivate' : 'Activate' }}</button></form></td></tr>@empty<tr><td class="empty-state" colspan="6">No course offerings created.</td></tr>@endforelse
        </tbody></table></div>
        @foreach ($courses as $course)<form class="inline-form catalog-forms" method="POST" action="{{ route('admin.courses.offerings.store', $course) }}">@csrf<strong>{{ $course->code }}</strong><select name="semester_id" required><option value="">Session / semester</option>@foreach ($semesters as $semester)<option value="{{ $semester->id }}">{{ $semester->academicSession->name }} · {{ $semester->name }}</option>@endforeach</select><select name="academic_program_id"><option value="">All programs</option>@foreach ($programs->where('department_id', $course->department_id) as $program)<option value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</select><select name="academic_level_id"><option value="">All levels</option>@foreach ($levels->where('department_id', $course->department_id) as $level)<option value="{{ $level->id }}">{{ $level->name }}</option>@endforeach</select><button class="button button-small" type="submit">Add offering</button></form>@endforeach
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
