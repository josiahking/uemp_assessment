@extends('layouts.app')

@section('title', 'University setup')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">University / Setup</p><h1>University structure</h1><p class="muted">Create departments and provision departmental administrators.</p></div></div>
    <section class="data-section"><div class="table-toolbar"><div><h2>Departments</h2><p>{{ $departments->count() }} departments</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Department</th><th>Code</th><th>Accounts</th></tr></thead><tbody>@forelse ($departments as $department)<tr><td><strong>{{ $department->name }}</strong></td><td>{{ $department->code }}</td><td>{{ $department->users_count }}</td></tr>@empty<tr><td class="empty-state" colspan="3">No departments created yet.</td></tr>@endforelse</tbody></table></div>
    </section>
    <div class="form-grid setup-grid">
        <section class="form-section"><div class="section-heading"><div><p class="eyebrow">University structure</p><h2>Create a department</h2></div></div>
            <form class="stacked-form" method="POST" action="{{ route('super-admin.departments.store') }}">@csrf<label>Department name<input name="name" required></label><label>Department code<input name="code" maxlength="20" required></label><button class="button button-primary" type="submit">Create department</button></form>
        </section>
        <section class="form-section"><div class="section-heading"><div><p class="eyebrow">Access management</p><h2>Create a Department Admin</h2></div></div>
            <form class="stacked-form" method="POST" action="{{ route('super-admin.administrators.store') }}">@csrf<label>Full name<input name="name" required></label><label>Email address<input name="email" type="email" required></label><label>Temporary password<input name="password" type="password" minlength="8" required></label><label>Department<select name="department_id" required><option value="">Choose department</option>@foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></label><button class="button button-primary" type="submit">Create administrator</button></form>
        </section>
    </div>
    <section class="data-section"><div class="table-toolbar"><div><h2>Department Administrators</h2><p>{{ $administrators->count() }} administrators</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Administrator</th><th>Email</th><th>Department</th></tr></thead><tbody>@forelse ($administrators as $administrator)<tr><td><strong>{{ $administrator->name }}</strong></td><td>{{ $administrator->email }}</td><td>{{ $administrator->department?->name ?? 'Unassigned' }}</td></tr>@empty<tr><td class="empty-state" colspan="3">No Department Admin accounts have been created.</td></tr>@endforelse</tbody></table></div>
    </section>
@endsection