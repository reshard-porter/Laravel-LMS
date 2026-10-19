@extends('layouts.app')

@section('title', $student->full_name)

@section('content')
<x-page-head :title="$student->full_name"
             :sub="$student->student_number.', Year '.$student->year_level.', '.(\App\Models\Student::STATUSES[$student->status] ?? $student->status)"
             :crumbs="['Students' => route('students.index')]">
    <a href="{{ route('students.report-card', $student) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener"><i class="bi bi-printer"></i> Report card</a>
    @can('admin')
        <a href="{{ route('students.edit', $student) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a>
        <form method="POST" action="{{ route('students.destroy', $student) }}" data-confirm="Delete {{ $student->full_name }}? Their grades and attendance will be deleted too. This can’t be undone.">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Delete</button>
        </form>
    @endcan
</x-page-head>

<div class="row g-3">
    <div class="col-lg-4">
        <section class="sheet mb-3">
            <div class="sheet-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="small-muted">Overall average</div>
                    <div class="display-font fs-2 lh-1 mt-1">{{ $summary['overall'] !== null ? number_format($summary['overall'], 1).'%' : '—' }}</div>
                </div>
                <x-grade :percent="$summary['overall']" :letter="$summary['overallLetter']" :show-percent="false" style="transform: scale(1.5); transform-origin: right center" />
            </div>
        </section>
        <section class="sheet">
            <div class="sheet-head"><h2>Details</h2></div>
            <div class="sheet-body">
                <dl class="mb-0">
                    <dt class="small-muted fw-normal">Email</dt><dd>{{ $student->email ?: '—' }}</dd>
                    <dt class="small-muted fw-normal">Date of birth</dt>
                    <dd>{{ $student->date_of_birth ? $student->date_of_birth->format('F j, Y').' (age '.$student->age.')' : '—' }}</dd>
                    <dt class="small-muted fw-normal">Guardian</dt><dd>{{ $student->guardian_name ?: '—' }}</dd>
                    <dt class="small-muted fw-normal">Guardian phone</dt><dd class="mb-0">{{ $student->guardian_phone ?: '—' }}</dd>
                </dl>
            </div>
        </section>
    </div>

    <div class="col-lg-8">
        <section class="sheet">
            <div class="sheet-head"><h2>Classes and grades</h2><span class="hint">{{ count($summary['rows']) }} enrolled</span></div>
            <div class="sheet-flush">
                @forelse ($summary['rows'] as $row)
                    @php($c = $row['classroom'])
                    <details class="border-top px-3 py-2" style="border-color: var(--line-2) !important" @if ($loop->first) open @endif>
                        <summary class="d-flex flex-wrap align-items-center gap-3 py-1" style="list-style: none; cursor: pointer">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $c->name }} <span class="small-muted fw-normal">{{ $c->code }}</span></div>
                                <div class="small-muted">{{ $c->teacher?->full_name ?? 'No teacher assigned' }}</div>
                            </div>
                            <div class="text-end small-muted" style="min-width: 7rem">
                                Attendance <strong class="text-body">{{ $row['attendance'] !== null ? number_format($row['attendance'], 0).'%' : '—' }}</strong>
                            </div>
                            @if ($row['missing'])
                                <span class="flag flag-warning"><i class="bi bi-exclamation-triangle"></i>{{ $row['missing'] }} missing</span>
                            @endif
                            <x-grade :percent="$row['percent']" :letter="$row['letter']" />
                        </summary>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Assignment</th><th>Category</th><th>Due</th><th class="num">Score</th><th class="num">%</th></tr></thead>
                                <tbody>
                                @foreach ($row['work'] as $w)
                                    @php($a = $w['assignment'])
                                    @php($g = $w['grade'])
                                    <tr>
                                        <td>{{ $a->title }}</td>
                                        <td><span class="chip chip-{{ $a->category }}">{{ $a->category_label }}</span></td>
                                        <td class="small-muted">{{ $a->due_date?->format('M j') ?? '—' }}</td>
                                        <td class="num">
                                            @if ($g?->excused) <span class="muted fst-italic">Excused</span>
                                            @elseif ($g?->score !== null) {{ rtrim(rtrim(number_format($g->score, 2), '0'), '.') }} / {{ rtrim(rtrim(number_format($a->max_points, 2), '0'), '.') }}
                                            @elseif ($a->isPastDue()) <span class="flag flag-warning">Missing</span>
                                            @else <span class="muted">Not due</span>
                                            @endif
                                        </td>
                                        <td class="num">{{ ($g && $g->score !== null && ! $g->excused) ? number_format($g->score / $a->max_points * 100, 0).'%' : '' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @can('manage-classroom', $c)
                            <a class="small" href="{{ route('classrooms.gradebook', $c) }}">Open the {{ $c->name }} gradebook</a>
                        @endcan
                    </details>
                @empty
                    <div class="empty"><i class="bi bi-easel"></i>Not enrolled in any classes. Enrol students from a class’s roster tab.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
