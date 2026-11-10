<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | UEMP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="portal-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ auth()->user()->hasRole('Super Admin') ? route('super-admin.dashboard') : route('dashboard') }}"><span class="brand-mark">U</span><span><strong>UEMP</strong><small>University portal</small></span></a>
            <div class="nav-label">Workspace</div>
            <nav class="primary-nav" aria-label="Main navigation">
                <a class="nav-item {{ request()->routeIs('dashboard', 'super-admin.dashboard') ? 'is-active' : '' }}" href="{{ auth()->user()->hasRole('Super Admin') ? route('super-admin.dashboard') : route('dashboard') }}"><span class="nav-icon">01</span>Overview</a>
                @if (auth()->user()->hasAnyRole(['Department Admin', 'Super Admin']))
                    <a class="nav-item {{ request()->routeIs('admin.students.*') ? 'is-active' : '' }}" href="{{ route('admin.students.index') }}"><span class="nav-icon">02</span>Students</a>
                    <a class="nav-item {{ request()->routeIs('admin.lecturers.*') ? 'is-active' : '' }}" href="{{ route('admin.lecturers.index') }}"><span class="nav-icon">03</span>Lecturers</a>
                    <a class="nav-item {{ request()->routeIs('admin.courses.*') ? 'is-active' : '' }}" href="{{ route('admin.courses.index') }}"><span class="nav-icon">04</span>Courses</a>
                    <a class="nav-item {{ request()->routeIs('admin.academics.*', 'admin.programs.*', 'admin.academic-levels.*') ? 'is-active' : '' }}" href="{{ route('admin.academics.index') }}"><span class="nav-icon">04A</span>Programs &amp; levels</a>
                    <a class="nav-item {{ request()->routeIs('admin.registrations.*') ? 'is-active' : '' }}" href="{{ route('admin.registrations.index') }}"><span class="nav-icon">05</span>Registrations</a>
                    <a class="nav-item {{ request()->routeIs('admin.results.*') ? 'is-active' : '' }}" href="{{ route('admin.results.index') }}"><span class="nav-icon">06</span>Results</a>
                    <a class="nav-item {{ request()->routeIs('admin.reports.*') ? 'is-active' : '' }}" href="{{ route('admin.reports.index') }}"><span class="nav-icon">07</span>Reports</a>
                    @if (auth()->user()->hasRole('Super Admin'))
                        <a class="nav-item {{ request()->routeIs('super-admin.academics.*', 'super-admin.departments.*') ? 'is-active' : '' }}" href="{{ route('super-admin.academics.index') }}"><span class="nav-icon">08</span>Academic setup</a>
                        <a class="nav-item {{ request()->routeIs('super-admin.accounts.*') ? 'is-active' : '' }}" href="{{ route('super-admin.accounts.index') }}"><span class="nav-icon">09</span>Accounts</a>
                        <a class="nav-item {{ request()->routeIs('super-admin.roles.*') ? 'is-active' : '' }}" href="{{ route('super-admin.roles.index') }}"><span class="nav-icon">10</span>Roles &amp; permissions</a>
                    @endif
                @elseif (auth()->user()->hasRole('Lecturer'))
                    <a class="nav-item {{ request()->routeIs('lecturer.courses.*') ? 'is-active' : '' }}" href="{{ route('lecturer.courses.index') }}"><span class="nav-icon">02</span>My courses</a>
                @else
                    <a class="nav-item {{ request()->routeIs('student.courses.*') ? 'is-active' : '' }}" href="{{ route('student.courses.index') }}"><span class="nav-icon">02</span>Course registration</a>
                @endif
            </nav>
            <div class="sidebar-footer"><div class="user-chip"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->getRoleNames()->first() }}</small></span></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="signout" type="submit">Sign out</button></form></div>
        </aside>
        <div class="main-column">
            <header class="topbar"><span>{{ auth()->user()->department?->name ?? 'University-wide access' }}</span><span class="topbar-status"><i></i> {{ \App\Models\AcademicSession::where('active', true)->value('name') ?? 'No active academic session' }}</span></header>
            <main class="content">
                @if (session('status'))<div class="notice notice-success" role="status">{{ session('status') }}</div>@endif
                @if ($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>