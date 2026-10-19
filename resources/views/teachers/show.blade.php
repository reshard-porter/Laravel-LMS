@extends('layouts.app')

@section('title', $teacher->full_name)

@section('content')
<x-page-head :title="$teacher->full_name" :sub="$teacher->department" :crumbs="['Teachers' => route('teachers.index')]">
    @can('admin')
        <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a>
        <form method="POST" action="{{ route('teachers.destroy', $teacher) }}" data-confirm="Remove {{ $teacher->full_name }}? Their classes will stay but become unassigned.">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Remove</button>
        </form>
    @endcan
</x-page-head>

<div class="row g-3">
    <div class="col-lg-4">
        <section class="sheet">
            <div class="sheet-head"><h2>Contact and employment</h2></div>
            <div class="sheet-body">
                <dl class="mb-0">
                    <dt class="small-muted fw-normal">Email</dt><dd><a href="mailto:{{ $teacher->email }}">{{ $teacher->email }}</a></dd>
                    <dt class="small-muted fw-normal">Phone</dt><dd>{{ $teacher->phone ?: '—' }}</dd>
                    <dt class="small-muted fw-normal">Hired</dt><dd>{{ $teacher->hire_date?->format('F j, Y') ?? '—' }}</dd>
                    @can('admin')
                        <dt class="small-muted fw-normal">Salary</dt><dd>{{ number_format($teacher->salary) }}</dd>
                    @endcan
                    <dt class="small-muted fw-normal">Sign-in</dt>
                    <dd class="mb-0">{{ $teacher->user ? 'Signs in as '.$teacher->user->email : 'No sign-in yet. Set a password on the edit page to create one.' }}</dd>
                </dl>
            </div>
        </section>
    </div>
    <div class="col-lg-8">
        <section class="sheet">
            <div class="sheet-head"><h2>Classes</h2><span class="hint">{{ $teacher->classrooms->count() }} this term</span></div>
            <div class="sheet-flush">
                @if ($teacher->classrooms->isEmpty())
                    <div class="empty"><i class="bi bi-easel"></i>Not teaching any classes yet.</div>
                @else
                    <table class="table table-hover align-middle">
                        <thead><tr><th class="first">Class</th><th>Schedule</th><th>Room</th><th class="num last">Students</th></tr></thead>
                        <tbody>
                        @foreach ($teacher->classrooms as $c)
                            <tr>
                                <td class="first">
                                    @can('manage-classroom', $c)
                                        <a class="row-link" href="{{ route('classrooms.show', $c) }}">{{ $c->name }}</a>
                                    @else
                                        <strong>{{ $c->name }}</strong>
                                    @endcan
                                    <div class="small-muted">{{ $c->code }}</div>
                                </td>
                                <td>{{ $c->schedule ?: '—' }}</td>
                                <td>{{ $c->room ?: '—' }}</td>
                                <td class="num last">{{ $c->students_count }} / {{ $c->capacity }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
