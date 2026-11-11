@extends('layouts.app')

@section('title', 'Account access')
@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">University / Access</p>
            <h1>Accounts and roles</h1>
            <p class="muted">Manage staff role assignments, departments, and account availability.</p>
        </div>
    </div>

    <section class="data-section">
        <div class="table-toolbar">
            <div><h2>University accounts</h2><p>{{ $users->total() }} accounts</p></div>
            <form class="filter-toolbar" method="GET" action="{{ route('super-admin.accounts.index') }}">
                <input name="q" type="search" value="{{ $filters['q'] }}" placeholder="Name or email" aria-label="Search accounts">
                <select name="role" aria-label="Role">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)<option value="{{ $role->name }}" @selected($filters['role'] === $role->name)>{{ $role->name }}</option>@endforeach
                </select>
                <select name="account_status" aria-label="Account status">
                    <option value="">All statuses</option>
                    @foreach (['active', 'deactivated', 'suspended'] as $status)<option value="{{ $status }}" @selected($filters['account_status'] === $status)>{{ ucfirst($status) }}</option>@endforeach
                </select>
                <select name="department_id" aria-label="Department">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)<option value="{{ $department->id }}" @selected((string) $filters['department_id'] === (string) $department->id)>{{ $department->name }}</option>@endforeach
                </select>
                <button class="button button-small" type="submit">Filter</button>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Account</th><th>Department</th><th>Role</th><th>Status</th><th>Update access</th></tr></thead>
                <tbody>
                    @forelse ($users as $account)
                        <tr data-searchable>
                            <td><strong>{{ $account->name }}</strong><small>{{ $account->email }}</small></td>
                            <td>{{ $account->department?->name ?? 'University-wide' }}</td>
                            <td>{{ $account->getRoleNames()->join(', ') }}</td>
                            <td><span class="status status-{{ $account->account_status === 'active' ? 'approved' : 'rejected' }}">{{ ucfirst($account->account_status) }}</span></td>
                            <td>
                                <details>
                                    <summary>Manage access</summary>
                                    <form class="stacked-inline-form" method="POST" action="{{ route('super-admin.accounts.update', $account) }}">
                                        @csrf @method('PATCH')
                                        <select name="role" aria-label="Role for {{ $account->name }}" required>
                                            @foreach ($roles as $role)<option value="{{ $role->name }}" @selected($account->hasRole($role->name))>{{ $role->name }}</option>@endforeach
                                        </select>
                                        <select name="account_status" aria-label="Status for {{ $account->name }}" required>
                                            @foreach (['active', 'deactivated', 'suspended'] as $status)<option value="{{ $status }}" @selected($account->account_status === $status)>{{ ucfirst($status) }}</option>@endforeach
                                        </select>
                                        <select name="department_id" aria-label="Department for {{ $account->name }}">
                                            <option value="">No department</option>
                                            @foreach ($departments as $department)<option value="{{ $department->id }}" @selected($account->department_id === $department->id)>{{ $department->name }}</option>@endforeach
                                        </select>
                                        <button class="button button-small" type="submit">Save</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="5">No accounts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $users->links() }}</div>
    </section>

    <section class="form-section">
        <div class="section-heading"><div><p class="eyebrow">Access management</p><h2>Create an account</h2></div></div>
        <form class="form-grid" method="POST" action="{{ route('super-admin.accounts.store') }}">
            @csrf
            <label>Name<input name="name" value="{{ old('name') }}" required></label>
            <label>Email<input name="email" type="email" value="{{ old('email') }}" required></label>
            <label>Temporary password<x-password-input name="password" minlength="8" required /></label>
            <label>Role
                <select name="role" required>
                    <option value="">Choose role</option>
                    @foreach ($roles as $role)<option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ $role->name }}</option>@endforeach
                </select>
            </label>
            <label>Department
                <select name="department_id">
                    <option value="">University-wide</option>
                    @foreach ($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>@endforeach
                </select>
            </label>
            <div class="form-actions"><button class="button button-primary" type="submit">Create account</button></div>
        </form>
    </section>
@endsection
