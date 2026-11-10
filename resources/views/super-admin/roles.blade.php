@extends('layouts.app')

@section('title', 'Roles and permissions')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">University / Access</p><h1>Roles &amp; permissions</h1><p class="muted">Define reusable roles and choose the permissions granted to each.</p></div></div>
    <section class="form-section"><div class="section-heading"><div><p class="eyebrow">Access management</p><h2>Create a role</h2></div></div>
        <form class="inline-form" method="POST" action="{{ route('super-admin.roles.store') }}">@csrf<input name="name" placeholder="Role name" required><button class="button button-primary" type="submit">Create role</button></form>
    </section>
    @foreach ($roles as $role)
        <section class="form-section role-section"><div class="section-heading"><div><p class="eyebrow">Role</p><h2>{{ $role->name }}</h2></div><span class="muted">{{ $role->permissions->count() }} permissions</span></div>
            <form method="POST" action="{{ route('super-admin.roles.permissions', $role) }}">@csrf @method('PUT')
                <div class="permission-grid">@foreach ($permissions as $permission)<label class="permission-option"><input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($role->hasPermissionTo($permission))><span>{{ str($permission->name)->replace('.', ' ')->title() }}</span></label>@endforeach</div>
                <div class="form-actions"><button class="button button-primary" type="submit">Save permissions</button></div>
            </form>
        </section>
    @endforeach
@endsection