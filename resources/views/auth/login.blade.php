<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ config('lms.school_name') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="login-page">
    <section class="login-board">
        <div class="ruled" aria-hidden="true"></div>
        <div class="rail-brand p-0 position-relative">
            <span class="mark" aria-hidden="true">L</span>
            <span class="name">LMS<span class="school">{{ config('lms.school_name') }}</span></span>
        </div>
        <div class="position-relative">
            <h1>Every class, every grade, <span class="underline">one place.</span></h1>
            <p class="mt-3">Take attendance, enter scores and see who needs help before the report cards go home.</p>
        </div>
        <p class="small mb-0 position-relative">{{ config('lms.current_term') }}</p>
    </section>

    <section class="login-form">
        <form method="POST" action="{{ route('login.attempt') }}" novalidate>
            @csrf
            <h2 class="h1 mb-1">Sign in</h2>
            <p class="muted mb-4">Use your school email and password.</p>

            @if (session('success'))
                <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" autofocus required
                       class="form-control form-control-lg @error('email') is-invalid @enderror">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       class="form-control form-control-lg @error('password') is-invalid @enderror">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                <label class="form-check-label" for="remember">Keep me signed in</label>
            </div>
            <button class="btn btn-primary btn-lg w-100" type="submit">Sign in</button>

            @if (app()->environment('local'))
                <div class="demo-accounts mt-4">
                    <strong>Demo accounts</strong> (password <code>password</code>)<br>
                    Admin: <code>admin@lms.test</code><br>
                    Teacher: <code>reshard@lms.test</code>
                </div>
            @endif
        </form>
    </section>
</div>
</body>
</html>
