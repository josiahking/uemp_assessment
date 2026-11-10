<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | UEMP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="login-page">
        <section class="login-aside">
            <a class="brand" href="/"><span class="brand-mark">U</span><span><strong>UEMP</strong><small>University portal</small></span></a>
            <h1>Academic work, in one place.</h1>
            <p>Department operations, course registration, and result review for the university community.</p>
        </section>
        <section class="login-panel">
            <div class="login-form-wrap">
                <p class="eyebrow">Secure university access</p>
                <h2>Welcome back</h2>
                <p>Sign in with your university account to continue.</p>
                @if ($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
                <form class="login-form" method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password">
                    <label class="remember" for="remember"><input id="remember" name="remember" type="checkbox" value="1"> Keep me signed in</label>
                    <button class="button button-primary" type="submit">Sign in</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>