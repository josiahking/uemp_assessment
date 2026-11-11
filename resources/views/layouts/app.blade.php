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
            <div class="sidebar-header">
                <a class="brand" href="{{ auth()->user()->hasRole('Super Admin') ? route('super-admin.dashboard') : route('dashboard') }}"><span class="brand-mark">U</span><span><strong>UEMP</strong><small>University portal</small></span></a>
                <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" aria-label="Open main menu" data-menu-toggle>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
            </div>
            <div class="nav-label">Workspace</div>
            <nav id="primary-nav" class="primary-nav" aria-label="Main navigation">
                <a class="nav-item {{ request()->routeIs('dashboard', 'super-admin.dashboard') ? 'is-active' : '' }}" href="{{ auth()->user()->hasRole('Super Admin') ? route('super-admin.dashboard') : route('dashboard') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z" /></svg></span>Overview</a>
                @if (auth()->user()->hasAnyRole(['Department Admin', 'Super Admin']))
                    <a class="nav-item {{ request()->routeIs('admin.students.*') ? 'is-active' : '' }}" href="{{ route('admin.students.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3" /><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5.5a3 3 0 0 1 0 5.8M18 14a5 5 0 0 1 3 4.5v1" /></svg></span>Students</a>
                    <a class="nav-item {{ request()->routeIs('admin.lecturers.*') ? 'is-active' : '' }}" href="{{ route('admin.lecturers.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="4" /><path d="M4 21a8 8 0 0 1 16 0M19 8h3M20.5 6.5v3" /></svg></span>Lecturers</a>
                    <a class="nav-item {{ request()->routeIs('admin.courses.*') ? 'is-active' : '' }}" href="{{ route('admin.courses.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22zM4 5.5v14A2.5 2.5 0 0 1 6.5 17H20" /></svg></span>Courses</a>
                    <a class="nav-item {{ request()->routeIs('admin.academics.*', 'admin.programs.*', 'admin.academic-levels.*') ? 'is-active' : '' }}" href="{{ route('admin.academics.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m2 8 10-5 10 5-10 5zM6 10v5c3 3 9 3 12 0v-5M22 8v6" /></svg></span>Programs &amp; levels</a>
                    <a class="nav-item {{ request()->routeIs('admin.registrations.*') ? 'is-active' : '' }}" href="{{ route('admin.registrations.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="4" width="14" height="18" rx="2" /><path d="M9 4.5h6M9 11h6M9 15h6M9 19h3" /></svg></span>Registrations</a>
                    <a class="nav-item {{ request()->routeIs('admin.results.*') ? 'is-active' : '' }}" href="{{ route('admin.results.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2zM7 9h2M12 9h5M7 14h2M12 14h5M7 19h2M12 19h5" /></svg></span>Results</a>
                    <a class="nav-item {{ request()->routeIs('admin.reports.*') ? 'is-active' : '' }}" href="{{ route('admin.reports.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V11M10 20V5M16 20v-8M22 20H2" /></svg></span>Reports</a>
                    @if (auth()->user()->hasRole('Super Admin'))
                        <a class="nav-item {{ request()->routeIs('super-admin.academics.*', 'super-admin.departments.*') ? 'is-active' : '' }}" href="{{ route('super-admin.academics.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V5l7-3 7 3v16M9 9h1M14 9h1M9 13h1M14 13h1M10 21v-4h4v4" /></svg></span>Academic setup</a>
                        <a class="nav-item {{ request()->routeIs('super-admin.accounts.*') ? 'is-active' : '' }}" href="{{ route('super-admin.accounts.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10" cy="8" r="4" /><path d="M3 21a7 7 0 0 1 14 0M19 8v6M16 11h6" /></svg></span>Accounts</a>
                        <a class="nav-item {{ request()->routeIs('super-admin.roles.*') ? 'is-active' : '' }}" href="{{ route('super-admin.roles.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z" /><path d="m9 12 2 2 4-4" /></svg></span>Roles &amp; permissions</a>
                    @endif
                @elseif (auth()->user()->hasRole('Lecturer'))
                    <a class="nav-item {{ request()->routeIs('lecturer.courses.*') ? 'is-active' : '' }}" href="{{ route('lecturer.courses.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22zM4 5.5v14A2.5 2.5 0 0 1 6.5 17H20" /></svg></span>My courses</a>
                @else
                    <a class="nav-item {{ request()->routeIs('student.courses.*') ? 'is-active' : '' }}" href="{{ route('student.courses.index') }}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3" /><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5.5a3 3 0 0 1 0 5.8M18 14a5 5 0 0 1 3 4.5v1" /></svg></span>Course registration</a>
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