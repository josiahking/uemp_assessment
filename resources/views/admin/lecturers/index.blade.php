@extends('layouts.app')

@section('title', 'Lecturers')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">People / Department</p><h1>Lecturers</h1><p class="muted">Manage teaching staff and departmental assignments.</p></div><a class="button button-primary" href="#new-lecturer">Add lecturer</a></div>
    <section class="data-section"><div class="table-toolbar"><div><h2>Lecturer directory</h2><p>{{ $lecturers->count() }} records</p></div><input class="search-input" type="search" placeholder="Search lecturers" aria-label="Search lecturers" data-table-search></div>
        <div class="table-wrap"><table><thead><tr><th>Lecturer</th><th>Email</th><th>Department</th><th>Assigned courses</th><th>Manage</th></tr></thead><tbody>
            @forelse ($lecturers as $lecturer)
                <tr data-searchable>
                    <td><strong>{{ $lecturer->name }}</strong></td>
                    <td>{{ $lecturer->email }}</td>
                    <td>{{ $lecturer->department?->name ?? 'Unassigned' }}</td>
                    <td>{{ $lecturer->courses()->count() }}</td>
                    <td>
                        <details>
                            <summary>Edit profile</summary>
                            <form class="stacked-inline-form" method="POST" action="{{ route('admin.lecturers.update', $lecturer) }}">
                                @csrf @method('PATCH')
                                <input name="name" aria-label="Lecturer name" value="{{ $lecturer->name }}" required>
                                <input name="email" type="email" aria-label="Lecturer email" value="{{ $lecturer->email }}" required>
                                <select name="faculty" aria-label="Faculty">
                                    <option value="">Choose faculty</option>
                                    @if ($lecturer->faculty && ! $faculties->contains('name', $lecturer->faculty))<option value="{{ $lecturer->faculty }}" selected>{{ $lecturer->faculty }} (current)</option>@endif
                                    @foreach ($faculties as $faculty)<option value="{{ $faculty->name }}" @selected($lecturer->faculty === $faculty->name)>{{ $faculty->name }}</option>@endforeach
                                </select>
                                <select name="account_status" aria-label="Account status">
                                    <option value="active" @selected($lecturer->account_status === 'active')>Active</option>
                                    <option value="deactivated" @selected($lecturer->account_status === 'deactivated')>Deactivated</option>
                                    <option value="suspended" @selected($lecturer->account_status === 'suspended')>Suspended</option>
                                </select>
                                <button class="button button-small" type="submit">Save</button>
                            </form>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td class="empty-state" colspan="5">No lecturers found for this department.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
    <section class="form-section" id="new-lecturer"><div class="section-heading"><div><p class="eyebrow">New record</p><h2>Add a lecturer</h2></div></div>
        <form class="form-grid" method="POST" action="{{ route('admin.lecturers.store') }}">
            @csrf
            @if ($departments->isNotEmpty())<label>Department<select name="department_id" required><option value="">Choose department</option>@foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></label>@endif
            <label>Full name<input name="name" value="{{ old('name') }}" required></label>
            <label>Email address<input name="email" type="email" value="{{ old('email') }}" required></label>
            <label>Temporary password<x-password-input name="password" minlength="8" required /></label>
            <label>Faculty
                <select name="faculty">
                    <option value="">Choose faculty</option>
                    @foreach ($faculties as $faculty)<option value="{{ $faculty->name }}" @selected(old('faculty') === $faculty->name)>{{ $faculty->name }}</option>@endforeach
                </select>
            </label>
            <div class="form-actions"><button class="button button-primary" type="submit">Create lecturer</button></div>
        </form>
    </section>
@endsection
