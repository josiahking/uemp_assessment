<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | UEMP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main>
        <h1>University Education Management Portal</h1>
        <p>Sign in with your university account.</p>

        @if ($errors->any())
            <div role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">

            <label for="remember">
                <input id="remember" name="remember" type="checkbox" value="1">
                Keep me signed in
            </label>

            <button type="submit">Sign in</button>
        </form>
    </main>
</body>
</html>