@extends('layouts.app')

@section('title', 'Students')

@section('content')
<x-page-head title="Students" :sub="$students->total().' '.\Illuminate\Support\Str::plural('student', $students->total()).(request()->hasAny(['q', 'status', 'classroom']) ? ' match your filters' : ' on record')">
    @can('admin')
        <a href="{{ route('students.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add student</a>
    @endcan
</x-page-head>

<div class="sheet">
    <form class="d-flex flex-wrap gap-2 p-3 border-bottom" method="GET" role="search" style="border-color: var(--line-2) !important">
        <div class="search flex-grow-1" style="max-width: 360px">
            <i class="bi bi-search"></i>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by name, number or email" aria-label="Search students">
        </div>
        <select name="classroom" class="form-select w-auto" aria-label="Filter by class" onchange="this.form.submit()">
            <option value="">All classes</option>
            @foreach ($classrooms as $c)
                <option value="{{ $c->id }}" @selected(request('classroom') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="status" class="form-select w-auto" aria-label="Filter by status" onchange="this.form.submit()">
            <option value="">Any status</option>
            @foreach (\App\Models\Student::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-secondary" type="submit">Search</button>
        @if (request()->hasAny(['q', 'status', 'classroom']))
            <a class="btn btn-link" href="{{ route('students.index') }}">Clear</a>
        @endif
    </form>

    @if ($students->isEmpty())
        <div class="empty"><i class="bi bi-mortarboard"></i>No students found.</div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr>
                    <th class="first">Name</th><th>Student no.</th><th class="num">Year</th><th class="num">Age</th><th class="num">Classes</th><th>Guardian</th><th class="last">Status</th>
                </tr></thead>
                <tbody>
                @foreach ($students as $s)
                    <tr>
                        <td class="first">
                            <a class="row-link" href="{{ route('students.show', $s) }}">{{ $s->sort_name }}</a>
                            @if ($s->email)<div class="small-muted">{{ $s->email }}</div>@endif
                        </td>
                        <td class="font-monospace small">{{ $s->student_number }}</td>
                        <td class="num">{{ $s->year_level }}</td>
                        <td class="num">{{ $s->age ?? '—' }}</td>
                        <td class="num">{{ $s->classrooms_count }}</td>
                        <td>{{ $s->guardian_name ?: '—' }}</td>
                        <td class="last">
                            <span class="flag {{ $s->status === 'active' ? 'flag-good' : 'flag-neutral' }}">{{ \App\Models\Student::STATUSES[$s->status] ?? $s->status }}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($students->hasPages())<div class="p-3">{{ $students->links() }}</div>@endif
    @endif
</div>
@endsection
