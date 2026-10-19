@extends('layouts.app')

@section('title', $classroom->name)

@php
    $tab = in_array(request('tab'), ['roster', 'assignments']) ? request('tab') : 'overview';
    $classAvg = $book->classAverage();
    $dist = $book->letterDistribution();
    $seatsLeft = $classroom->capacity - $book->students->count();
@endphp

@section('content')
@include('classrooms._header', ['active' => 'overview'])

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link {{ $tab === 'overview' ? 'active' : '' }}" href="{{ route('classrooms.show', $classroom) }}">Summary</a></li>
    <li class="nav-item"><a class="nav-link {{ $tab === 'roster' ? 'active' : '' }}" href="{{ route('classrooms.show', [$classroom, 'tab' => 'roster']) }}">Roster <span class="muted">{{ $book->students->count() }}</span></a></li>
    <li class="nav-item"><a class="nav-link {{ $tab === 'assignments' ? 'active' : '' }}" href="{{ route('classrooms.show', [$classroom, 'tab' => 'assignments']) }}">Assignments <span class="muted">{{ $book->assignments->count() }}</span></a></li>
</ul>

@if ($tab === 'overview')
    <section class="glance">
        <div>
            <div class="v d-flex align-items-center gap-2">
                @if ($classAvg !== null)
                    <span class="letter letter-{{ \App\Services\GradeCalculator::letter($classAvg) }}">{{ \App\Services\GradeCalculator::letter($classAvg) }}</span>
                    <span>{{ number_format($classAvg, 1) }}<small>%</small></span>
                @else – @endif
            </div>
            <div class="k">Class average</div>
        </div>
        <div><div class="v">{{ $book->students->count() }}<small>/ {{ $classroom->capacity }}</small></div><div class="k">Students enrolled</div></div>
        <div><div class="v">{{ $book->assignments->count() }}</div><div class="k">Assignments</div></div>
        <div><div class="v">{{ $book->ungradedCount() }}</div><a class="k" href="{{ route('classrooms.gradebook', $classroom) }}">Overdue scores to enter</a></div>
    </section>

    <div class="row g-3">
        <div class="col-lg-7">
            <section class="sheet h-100">
                <div class="sheet-head"><h2>Grade weights</h2><span class="hint">{{ $classroom->usesCustomWeights() ? 'Set for this class' : 'School defaults' }}</span></div>
                <div class="sheet-body">
                    @foreach (config('lms.categories') as $key => $cat)
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <span class="chip chip-{{ $key }}" style="min-width: 6.5rem">{{ $cat['label'] }}</span>
                            <div class="meter flex-grow-1" aria-hidden="true"><span style="width: {{ $book->weights[$key] }}%"></span></div>
                            <span class="fw-semibold" style="min-width: 3rem; text-align: right">{{ rtrim(rtrim(number_format($book->weights[$key], 1), '0'), '.') }}%</span>
                        </div>
                    @endforeach
                    <a href="{{ route('classrooms.edit', $classroom) }}" class="small">Change weights</a>
                </div>
            </section>
        </div>
        <div class="col-lg-5">
            <section class="sheet h-100">
                <div class="sheet-head"><h2>Letter grades</h2></div>
                <div class="sheet-body">
                    @php($max = max(1, max($dist)))
                    @foreach ($dist as $letter => $n)
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <span class="letter letter-{{ $letter }}">{{ $letter }}</span>
                            <div class="meter flex-grow-1" aria-hidden="true"><span style="width: {{ $n / $max * 100 }}%; background: var(--series-1)"></span></div>
                            <span style="min-width: 5.5rem">{{ $n }} {{ \Illuminate\Support\Str::plural('student', $n) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
        @if ($classroom->description)
            <div class="col-12">
                <section class="sheet"><div class="sheet-head"><h2>About this class</h2></div><div class="sheet-body" style="max-width: 72ch">{!! nl2br(e($classroom->description)) !!}</div></section>
            </div>
        @endif
    </div>
@endif

@if ($tab === 'roster')
    <div class="row g-3">
        <div class="col-lg-8">
            <section class="sheet">
                <div class="sheet-flush">
                    @if ($book->students->isEmpty())
                        <div class="empty"><i class="bi bi-people"></i>No students enrolled yet. Add them from the list on the right.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr><th class="first">Student</th><th>Student no.</th><th>Enrolled</th><th>Grade</th><th class="last"></th></tr></thead>
                                <tbody>
                                @foreach ($book->students as $s)
                                    <tr>
                                        <td class="first"><a class="row-link" href="{{ route('students.show', $s) }}">{{ $s->sort_name }}</a></td>
                                        <td class="font-monospace small">{{ $s->student_number }}</td>
                                        <td class="small-muted">{{ $s->pivot->enrolled_on ? \Illuminate\Support\Carbon::parse($s->pivot->enrolled_on)->format('M j, Y') : '—' }}</td>
                                        <td><x-grade :percent="$book->results[$s->id]['percent']" :letter="$book->results[$s->id]['letter']" /></td>
                                        <td class="last text-end">
                                            <form method="POST" action="{{ route('classrooms.roster.destroy', [$classroom, $s]) }}" data-confirm="Remove {{ $s->full_name }} from {{ $classroom->name }}? Their scores and attendance for this class will be deleted.">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-secondary" type="submit">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>
        <div class="col-lg-4">
            <form method="POST" action="{{ route('classrooms.roster.store', $classroom) }}" class="sheet">
                @csrf
                <div class="sheet-head"><h2>Enrol students</h2><span class="hint">{{ max(0, $seatsLeft) }} seats left</span></div>
                <div class="sheet-body">
                    @error('student_ids')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                    @if ($available->isEmpty())
                        <p class="muted mb-0">Every active student is already in this class.</p>
                    @else
                        <input type="search" class="form-control form-control-sm mb-2" placeholder="Filter students" aria-label="Filter students"
                               oninput="const q=this.value.toLowerCase();document.querySelectorAll('#pick label').forEach(l=>l.parentElement.hidden=!l.textContent.toLowerCase().includes(q))">
                        <div id="pick" style="max-height: 340px; overflow: auto" class="border rounded p-2 mb-3">
                            @foreach ($available as $s)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="student_ids[]" value="{{ $s->id }}" id="pick{{ $s->id }}" @checked(in_array($s->id, old('student_ids', [])))>
                                    <label class="form-check-label" for="pick{{ $s->id }}">{{ $s->last_name }}, {{ $s->first_name }} <span class="small-muted">{{ $s->student_number }}</span></label>
                                </div>
                            @endforeach
                        </div>
                        <button class="btn btn-primary w-100" type="submit" @disabled($seatsLeft <= 0)>Enrol selected</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endif

@if ($tab === 'assignments')
    <section class="sheet">
        <div class="sheet-head">
            <h2>Assignments</h2>
            <a href="{{ route('classrooms.assignments.create', $classroom) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add assignment</a>
        </div>
        <div class="sheet-flush">
            @if ($book->assignments->isEmpty())
                <div class="empty"><i class="bi bi-journal-plus"></i>No assignments yet. Add one and it appears as a column in the gradebook.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th class="first">Assignment</th><th>Category</th><th>Due</th><th class="num">Points</th><th class="num">Class average</th><th class="last"></th></tr></thead>
                        <tbody>
                        @foreach ($book->assignments as $a)
                            <tr>
                                <td class="first">
                                    <a class="row-link" href="{{ route('classrooms.assignments.edit', [$classroom, $a]) }}">{{ $a->title }}</a>
                                    @if ($a->description)<div class="small-muted text-truncate" style="max-width: 40ch">{{ $a->description }}</div>@endif
                                </td>
                                <td><span class="chip chip-{{ $a->category }}">{{ $a->category_label }}</span></td>
                                <td>
                                    {{ $a->due_date?->format('D, M j') ?? '—' }}
                                    @if ($a->due_date && ! $a->isPastDue())<span class="small-muted"> upcoming</span>@endif
                                </td>
                                <td class="num">{{ rtrim(rtrim(number_format($a->max_points, 2), '0'), '.') }}</td>
                                <td class="num">{{ $book->assignmentAverages[$a->id] !== null ? number_format($book->assignmentAverages[$a->id], 1).'%' : '—' }}</td>
                                <td class="last text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('classrooms.assignments.edit', [$classroom, $a]) }}">Edit</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
@endif
@endsection
