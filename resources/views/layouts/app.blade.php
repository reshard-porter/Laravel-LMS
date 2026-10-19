<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('lms.school_name') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('head')
</head>
<body>
@php($user = auth()->user())
<div class="shell">
    <aside class="rail" id="rail" aria-label="Main navigation">
        <a class="rail-brand" href="{{ route('dashboard') }}">
            <span class="mark" aria-hidden="true">L</span>
            <span class="name">LMS<span class="school">{{ config('lms.school_name') }}</span></span>
        </a>

        <nav>
            <a class="rail-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-grid-1x2" aria-hidden="true"></i> Dashboard
            </a>
            <a class="rail-link {{ request()->routeIs('classrooms.*') ? 'active' : '' }}" href="{{ route('classrooms.index') }}">
                <i class="bi bi-easel" aria-hidden="true"></i> {{ $user->isAdmin() ? 'Classes' : 'My classes' }}
            </a>
            <a class="rail-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}">
                <i class="bi bi-mortarboard" aria-hidden="true"></i> Students
            </a>
            <a class="rail-link {{ request()->routeIs('teachers.*') ? 'active' : '' }}" href="{{ route('teachers.index') }}">
                <i class="bi bi-person-badge" aria-hidden="true"></i> Teachers
            </a>

            @php($myClasses = $user->manageableClassrooms()->orderBy('name')->limit(8)->get(['id', 'name', 'code']))
            @if ($myClasses->isNotEmpty())
                <div class="rail-section">Gradebooks</div>
                @foreach ($myClasses as $c)
                    <a class="rail-link {{ request()->route('classroom')?->id === $c->id && request()->routeIs('classrooms.gradebook') ? 'active' : '' }}"
                       href="{{ route('classrooms.gradebook', $c) }}">
                        <i class="bi bi-journal-check" aria-hidden="true"></i>
                        <span class="text-truncate">{{ $c->name }}</span>
                    </a>
                @endforeach
            @endif
        </nav>

        <div class="rail-foot">
            <div class="rail-user">
                <span class="avatar" aria-hidden="true">{{ $user->initials() }}</span>
                <span class="who">
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->isAdmin() ? 'Administrator' : 'Teacher' }}</span>
                </span>
            </div>
            <div class="d-flex">
                <a class="btn btn-link" href="{{ route('account.edit') }}"><i class="bi bi-gear"></i> Account</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-link" type="submit"><i class="bi bi-box-arrow-right"></i> Sign out</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="btn btn-sm text-white" type="button" id="railToggle" aria-controls="rail" aria-expanded="false">
                <i class="bi bi-list fs-4"></i><span class="visually-hidden">Menu</span>
            </button>
            <strong class="display-font">LMS</strong>
            <span class="avatar" aria-hidden="true">{{ $user->initials() }}</span>
        </header>

        <main class="content" id="content">
            @if (session('success'))
                <div class="alert alert-success d-flex align-items-center gap-2" role="status">
                    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script>
    (function () {
        const rail = document.getElementById('rail');
        const toggle = document.getElementById('railToggle');
        toggle?.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = rail.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open);
        });
        document.addEventListener('click', (e) => {
            if (rail.classList.contains('open') && !rail.contains(e.target)) {
                rail.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
        // Ask before deleting things.
        document.querySelectorAll('form[data-confirm]').forEach((f) => {
            f.addEventListener('submit', (e) => { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
        });
    })();
</script>
@stack('scripts')
</body>
</html>
