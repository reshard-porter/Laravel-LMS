@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = explode(' ', auth()->user()->name)[0];
@endphp

@section('content')
<x-page-head :title="$greeting.', '.$firstName"
             :sub="'Here’s how '.config('lms.current_term').' is going as of '.now()->format('l, F j').'.'">
    @can('admin')
        <a href="{{ route('students.create') }}" class="btn btn-outline-secondary"><i class="bi bi-person-plus"></i> Add student</a>
        <a href="{{ route('classrooms.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New class</a>
    @endcan
</x-page-head>

<section class="glance" aria-label="At a glance">
    <div>
        <div class="v">{{ number_format($stats['students']) }}</div>
        <a class="k" href="{{ route('students.index') }}">{{ auth()->user()->isAdmin() ? 'Active students' : 'Students in my classes' }}</a>
    </div>
    @if ($stats['teachers'] !== null)
        <div>
            <div class="v">{{ $stats['teachers'] }}</div>
            <a class="k" href="{{ route('teachers.index') }}">Teachers</a>
        </div>
    @endif
    <div>
        <div class="v">{{ $stats['classes'] }}</div>
        <a class="k" href="{{ route('classrooms.index') }}">Classes this term</a>
    </div>
    <div>
        <div class="v d-flex align-items-center gap-2">
            @if ($stats['average'] !== null)
                <span class="letter letter-{{ $stats['averageLetter'] }}">{{ $stats['averageLetter'] }}</span>
                <span>{{ number_format($stats['average'], 1) }}<small>%</small></span>
            @else
                –
            @endif
        </div>
        <div class="k">Average grade</div>
    </div>
    <div>
        <div class="v">{{ $stats['attendance'] !== null ? number_format($stats['attendance'], 1) : '–' }}<small>%</small></div>
        <div class="k">Attendance, last 30 days</div>
    </div>
    <div>
        <div class="v">{{ number_format($stats['needsGrading']) }}</div>
        <div class="k">Overdue scores to enter</div>
    </div>
</section>

@if ($classRows === [])
    <div class="sheet">
        <div class="empty">
            <i class="bi bi-easel"></i>
            <p class="mb-3">{{ auth()->user()->isAdmin() ? 'No classes yet. Create the first one to start the gradebook.' : 'You aren’t assigned to any classes yet. Ask an administrator to add you as a class teacher.' }}</p>
            @can('admin')<a class="btn btn-primary" href="{{ route('classrooms.create') }}">Create a class</a>@endcan
        </div>
    </div>
@else
<div class="row g-3">
    <div class="col-lg-7">
        <section class="sheet h-100">
            <div class="sheet-head">
                <h2>Class averages</h2>
                <span class="hint">Dashed line: at-risk threshold ({{ config('lms.at_risk_below') }}%)</span>
            </div>
            <div class="sheet-body">
                <div class="chart-box tall"><canvas id="classAvgChart" role="img" aria-label="Bar chart of average grade by class"></canvas></div>
                <details class="table-view">
                    <summary>Show as a table</summary>
                    <table class="table table-sm mt-2">
                        <thead><tr><th>Class</th><th class="num">Average</th></tr></thead>
                        <tbody>
                        @foreach ($classRows as $row)
                            <tr><td>{{ $row['classroom']->name }}</td><td class="num">{{ $row['average'] !== null ? number_format($row['average'], 1).'%' : '–' }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </details>
            </div>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="sheet h-100">
            <div class="sheet-head">
                <h2>Grade distribution</h2>
                <span class="hint">Final grades, all classes</span>
            </div>
            <div class="sheet-body">
                <div class="chart-box tall"><canvas id="distChart" role="img" aria-label="Bar chart of how many students have each letter grade"></canvas></div>
                <details class="table-view">
                    <summary>Show as a table</summary>
                    <table class="table table-sm mt-2">
                        <thead><tr><th>Letter</th><th class="num">Students</th></tr></thead>
                        <tbody>
                        @foreach ($letters as $letter => $n)
                            <tr><td>{{ $letter }}</td><td class="num">{{ $n }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </details>
            </div>
        </section>
    </div>

    <div class="col-lg-7">
        <section class="sheet h-100">
            <div class="sheet-head">
                <h2>Students who need attention</h2>
                <span class="hint">{{ $atRiskTotal }} below {{ config('lms.at_risk_below') }}% in a class</span>
            </div>
            <div class="sheet-flush">
                @if ($atRisk === [])
                    <div class="empty"><i class="bi bi-emoji-smile"></i>Every student is at or above {{ config('lms.at_risk_below') }}% in every class.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th class="first">Student</th><th>Class</th><th>Grade</th><th class="num last">Missing work</th></tr></thead>
                            <tbody>
                            @foreach ($atRisk as $r)
                                <tr>
                                    <td class="first"><a class="row-link" href="{{ route('students.show', $r['student']) }}">{{ $r['student']->full_name }}</a></td>
                                    <td><a href="{{ route('classrooms.gradebook', $r['classroom']) }}" class="text-decoration-none">{{ $r['classroom']->name }}</a></td>
                                    <td><x-grade :percent="$r['percent']" :letter="$r['letter']" /></td>
                                    <td class="num last">
                                        @if ($r['missing'] > 0)
                                            <span class="flag flag-warning"><i class="bi bi-exclamation-triangle"></i>{{ $r['missing'] }}</span>
                                        @else
                                            <span class="muted">0</span>
                                        @endif
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
    <div class="col-lg-5">
        <section class="sheet h-100">
            <div class="sheet-head">
                <h2>Due in the next two weeks</h2>
            </div>
            <div class="sheet-flush">
                @forelse ($upcoming as $a)
                    <div class="d-flex align-items-start gap-3 px-3 py-2 border-bottom" style="border-color: var(--line-2) !important">
                        <div class="text-center" style="min-width: 3rem">
                            <div class="small-muted">{{ $a->due_date->format('M') }}</div>
                            <div class="display-font fs-4 lh-1">{{ $a->due_date->format('j') }}</div>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold">{{ $a->title }}</div>
                            <div class="small-muted">{{ $a->classroom->name }} <span class="chip chip-{{ $a->category }} ms-1">{{ $a->category_label }}</span></div>
                        </div>
                        <div class="small-muted text-nowrap">{{ $a->due_date->isToday() ? 'Today' : $a->due_date->diffForHumans(['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW]) }}</div>
                    </div>
                @empty
                    <div class="empty"><i class="bi bi-calendar-check"></i>Nothing is due in the next 14 days.</div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="sheet h-100">
            <div class="sheet-head">
                <h2>Attendance by week</h2>
                <span class="hint">Late counts as attended</span>
            </div>
            <div class="sheet-body">
                <div class="chart-box"><canvas id="attChart" role="img" aria-label="Line chart of weekly attendance rate"></canvas></div>
                <details class="table-view">
                    <summary>Show as a table</summary>
                    <table class="table table-sm mt-2">
                        <thead><tr><th>Week of</th><th class="num">Attendance</th></tr></thead>
                        <tbody>
                        @foreach ($weeks as $w)
                            <tr><td>{{ $w['label'] }}</td><td class="num">{{ $w['rate'] !== null ? number_format($w['rate'], 1).'%' : 'No records' }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </details>
            </div>
        </section>
    </div>
    <div class="col-lg-7">
        <section class="sheet h-100">
            <div class="sheet-head"><h2>Classes</h2><a class="hint" href="{{ route('classrooms.index') }}">All classes</a></div>
            <div class="sheet-flush table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th class="first">Class</th><th>Teacher</th><th class="num">Students</th><th>Average</th><th class="num last">To grade</th></tr></thead>
                    <tbody>
                    @foreach ($classRows as $row)
                        <tr>
                            <td class="first">
                                <a class="row-link" href="{{ route('classrooms.show', $row['classroom']) }}">{{ $row['classroom']->name }}</a>
                                <div class="small-muted">{{ $row['classroom']->code }}</div>
                            </td>
                            <td>{{ $row['classroom']->teacher?->full_name ?? 'Unassigned' }}</td>
                            <td class="num">{{ $row['students'] }}</td>
                            <td><x-grade :percent="$row['average']" /></td>
                            <td class="num last">
                                @if ($row['ungraded'])
                                    <a href="{{ route('classrooms.gradebook', $row['classroom']) }}" class="flag flag-warning text-decoration-none">{{ $row['ungraded'] }}</a>
                                @else
                                    <span class="flag flag-good"><i class="bi bi-check2"></i>Up to date</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
<script>
(function () {
    if (!window.Chart) return;
    const css = getComputedStyle(document.documentElement);
    const v = (name) => css.getPropertyValue(name).trim();
    const series = v('--series-1'), grid = '#eaefec', ink2 = v('--ink-2'), ink3 = v('--ink-3');

    Chart.defaults.font.family = v('--font-body');
    Chart.defaults.font.size = 12;
    Chart.defaults.color = ink2;
    Chart.defaults.plugins.legend.display = false;
    Object.assign(Chart.defaults.plugins.tooltip, {
        backgroundColor: '#17201d', padding: 10, cornerRadius: 6, displayColors: false,
        titleFont: { weight: '600' },
    });

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) Chart.defaults.animation = false;

    // Draws a dashed vertical reference line at a given x value.
    const thresholdLine = {
        id: 'threshold',
        afterDatasetsDraw(chart, _args, opts) {
            if (opts.value == null) return;
            const { ctx, chartArea: a, scales: { x } } = chart;
            const px = x.getPixelForValue(opts.value);
            ctx.save();
            ctx.strokeStyle = '#d03b3b'; ctx.lineWidth = 1.5; ctx.setLineDash([5, 4]);
            ctx.beginPath(); ctx.moveTo(px, a.top); ctx.lineTo(px, a.bottom); ctx.stroke();
            ctx.restore();
        },
    };

    const classRows = @json(collect($classRows)->map(fn ($r) => ['name' => $r['classroom']->name, 'avg' => $r['average'] !== null ? round($r['average'], 1) : null])->values());
    new Chart(document.getElementById('classAvgChart'), {
        type: 'bar',
        data: {
            labels: classRows.map((r) => r.name),
            datasets: [{ data: classRows.map((r) => r.avg), backgroundColor: series, borderRadius: 4, borderSkipped: 'start', maxBarThickness: 18 }],
        },
        options: {
            indexAxis: 'y', maintainAspectRatio: false,
            scales: {
                x: { min: 0, max: 100, grid: { color: grid }, border: { display: false }, ticks: { callback: (t) => t + '%', color: ink3 } },
                y: { grid: { display: false }, border: { display: false } },
            },
            plugins: {
                threshold: { value: {{ (int) config('lms.at_risk_below') }} },
                tooltip: { callbacks: { label: (c) => c.raw == null ? 'No grades yet' : `Average ${c.raw}%` } },
            },
        },
        plugins: [thresholdLine],
    });

    const letters = @json($letters);
    new Chart(document.getElementById('distChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(letters),
            datasets: [{ data: Object.values(letters), backgroundColor: series, borderRadius: 4, borderSkipped: 'start', maxBarThickness: 44 }],
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: grid }, border: { display: false }, ticks: { precision: 0, color: ink3 } },
                x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 14, weight: '700' } } },
            },
            plugins: { tooltip: { callbacks: { title: (i) => `Grade ${i[0].label}`, label: (c) => `${c.raw} student${c.raw === 1 ? '' : 's'}` } } },
        },
    });

    const weeks = @json(collect($weeks)->map(fn ($w) => ['label' => $w['label'], 'rate' => $w['rate'] !== null ? round($w['rate'], 1) : null]));
    const rates = weeks.map((w) => w.rate).filter((r) => r !== null);
    new Chart(document.getElementById('attChart'), {
        type: 'line',
        data: {
            labels: weeks.map((w) => w.label),
            datasets: [{
                data: weeks.map((w) => w.rate), borderColor: series, backgroundColor: series, borderWidth: 2,
                pointRadius: 4, pointHoverRadius: 6, pointBorderColor: '#fff', pointBorderWidth: 2, tension: .25, spanGaps: true,
            }],
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: {
                    suggestedMin: rates.length ? Math.max(0, Math.floor(Math.min(...rates) / 5) * 5 - 5) : 0,
                    max: 100, grid: { color: grid }, border: { display: false }, ticks: { callback: (t) => t + '%', color: ink3, stepSize: 5 },
                },
                x: { grid: { display: false }, border: { display: false }, ticks: { color: ink3 } },
            },
            plugins: { tooltip: { callbacks: { title: (i) => `Week of ${i[0].label}`, label: (c) => c.raw == null ? 'No records' : `${c.raw}% attended` } } },
        },
    });
})();
</script>
@endpush
