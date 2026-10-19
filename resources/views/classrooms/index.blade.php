@extends('layouts.app')

@section('title', 'Classes')

@section('content')
<x-page-head :title="auth()->user()->isAdmin() ? 'Classes' : 'My classes'" :sub="$classrooms->count().' '.\Illuminate\Support\Str::plural('class', $classrooms->count())">
    @can('admin')
        <a href="{{ route('classrooms.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New class</a>
    @endcan
</x-page-head>

<div class="sheet">
    <form class="d-flex flex-wrap gap-2 p-3 border-bottom" method="GET" role="search" style="border-color: var(--line-2) !important">
        <div class="search flex-grow-1" style="max-width: 360px">
            <i class="bi bi-search"></i>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by name, code, subject or room" aria-label="Search classes">
        </div>
        @if ($terms->count() > 1)
            <select name="term" class="form-select w-auto" aria-label="Filter by term" onchange="this.form.submit()">
                <option value="">All terms</option>
                @foreach ($terms as $t)<option @selected(request('term') === $t)>{{ $t }}</option>@endforeach
            </select>
        @endif
    </form>

    @if ($classrooms->isEmpty())
        <div class="empty">
            <i class="bi bi-easel"></i>
            @if (request('q')) No classes match “{{ request('q') }}”.
            @elseif (auth()->user()->isAdmin()) No classes yet. <a href="{{ route('classrooms.create') }}">Create the first one</a>.
            @else You aren’t assigned to any classes yet.
            @endif
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr>
                    <th class="first">Class</th><th>Teacher</th><th>Schedule</th><th>Room</th><th>Enrolment</th><th class="num">Assignments</th><th class="last">Average</th>
                </tr></thead>
                <tbody>
                @foreach ($classrooms as $c)
                    <tr>
                        <td class="first">
                            <a class="row-link" href="{{ route('classrooms.show', $c) }}">{{ $c->name }}</a>
                            <div class="small-muted">{{ $c->code }}, {{ $c->term }}</div>
                        </td>
                        <td>{{ $c->teacher?->full_name ?? 'Unassigned' }}</td>
                        <td>{{ $c->schedule ?: '—' }}</td>
                        <td>{{ $c->room ?: '—' }}</td>
                        <td style="min-width: 140px">
                            <div class="d-flex align-items-center gap-2">
                                <div class="meter flex-grow-1" aria-hidden="true"><span style="width: {{ min(100, $c->capacity ? $c->students_count / $c->capacity * 100 : 0) }}%"></span></div>
                                <span class="small text-nowrap">{{ $c->students_count }} / {{ $c->capacity }}</span>
                            </div>
                        </td>
                        <td class="num">{{ $c->assignments_count }}</td>
                        <td class="last"><x-grade :percent="$averages[$c->id]" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
