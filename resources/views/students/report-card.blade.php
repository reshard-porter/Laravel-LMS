<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report card: {{ $student->full_name }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="no-print d-flex justify-content-center gap-2 mt-3">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print or save as PDF</button>
    <a class="btn btn-outline-secondary" href="{{ route('students.show', $student) }}">Back to profile</a>
</div>

<article class="report">
    <header class="report-head">
        <div>
            <div class="small-muted">{{ config('lms.school_name') }}, {{ config('lms.current_term') }} report card</div>
            <h1>{{ $student->full_name }}</h1>
            <div class="small-muted">Student {{ $student->student_number }}, Year {{ $student->year_level }}@if ($student->guardian_name), guardian {{ $student->guardian_name }}@endif</div>
        </div>
        <div class="overall">
            <div class="small-muted mb-1">Overall</div>
            @if ($summary['overallLetter'])
                <span class="letter letter-{{ $summary['overallLetter'] }}">{{ $summary['overallLetter'] }}</span>
                <div class="fw-semibold mt-1">{{ number_format($summary['overall'], 1) }}%</div>
            @else
                <span class="letter letter-none">–</span>
            @endif
        </div>
    </header>

    <table class="table align-middle">
        <thead>
            <tr>
                <th>Class</th>
                <th>Teacher</th>
                @foreach (config('lms.categories') as $key => $cat)
                    <th class="num">{{ $cat['label'] }}</th>
                @endforeach
                <th class="num">Attendance</th>
                <th class="num">Final</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($summary['rows'] as $row)
            <tr>
                <td><strong>{{ $row['classroom']->name }}</strong><div class="small-muted">{{ $row['classroom']->code }}</div></td>
                <td>{{ $row['classroom']->teacher?->full_name ?? '—' }}</td>
                @foreach (config('lms.categories') as $key => $cat)
                    <td class="num">{{ isset($row['categories'][$key]) ? number_format($row['categories'][$key], 0).'%' : '—' }}</td>
                @endforeach
                <td class="num">{{ $row['attendance'] !== null ? number_format($row['attendance'], 0).'%' : '—' }}</td>
                <td class="num"><x-grade :percent="$row['percent']" :letter="$row['letter']" /></td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center muted">No classes this term.</td></tr>
        @endforelse
        </tbody>
    </table>

    <p class="small-muted mt-4 mb-0">
        @php($scale = config('lms.letter_grades'))
        @php($keys = array_keys($scale))
        Letter grades: @foreach ($keys as $i => $l){{ $l }} {{ $i === 0 ? $scale[$l].'–100' : ($i === count($keys) - 1 ? 'below '.$scale[$keys[$i - 1]] : $scale[$l].'–'.($scale[$keys[$i - 1]] - 1)) }}@if (! $loop->last), @endif @endforeach.
        Category percentages are points earned out of points possible; the final grade weights them by each class’s category weights.
        Generated {{ now()->format('F j, Y') }}.
    </p>
</article>
</body>
</html>
