<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>No access · {{ config('lms.school_name') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="d-grid" style="place-items: center; min-height: 100vh">
    <div class="sheet p-4 text-center" style="max-width: 460px">
        <h1 class="h2">You don’t have access to this page</h1>
        <p class="muted">Teachers can open the classes they teach. Adding or removing people and classes is limited to administrators.</p>
        <a class="btn btn-primary" href="{{ url('/') }}">Go to the dashboard</a>
    </div>
</body>
</html>
