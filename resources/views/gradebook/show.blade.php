@extends('layouts.app')

@section('title', 'Gradebook: '.$classroom->name)

@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    $classAvg = $book->classAverage();
@endphp

@section('content')
@include('classrooms._header', ['active' => 'gradebook'])

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-octagon"></i>
        {{ $errors->count() }} {{ \Illuminate\Support\Str::plural('score', $errors->count()) }} couldn’t be saved. They’re highlighted in red. Nothing was saved, so fix them and save again.
    </div>
@endif

@if ($book->students->isEmpty() || $book->assignments->isEmpty())
    <div class="sheet">
        <div class="empty">
            <i class="bi bi-journal-check"></i>
            @if ($book->students->isEmpty())
                <p>This class has no students yet.</p>
                <a class="btn btn-primary" href="{{ route('classrooms.show', [$classroom, 'tab' => 'roster']) }}">Enrol students</a>
            @else
                <p>Add an assignment to start entering scores.</p>
                <a class="btn btn-primary" href="{{ route('classrooms.assignments.create', $classroom) }}">Add assignment</a>
            @endif
        </div>
    </div>
@else
<form method="POST" action="{{ route('classrooms.gradebook.update', $classroom) }}" id="gradebook" class="sheet" novalidate>
    @csrf @method('PUT')

    <div class="gb-toolbar">
        <button type="submit" class="btn btn-primary" id="saveBtn"><i class="bi bi-check2"></i> Save scores</button>
        <span class="gb-status" id="gbStatus" aria-live="polite">All changes saved</span>
        <div class="ms-auto d-flex flex-wrap gap-2 align-items-center">
            <span class="small-muted">Class average <strong class="text-body" id="classAvg">{{ $classAvg !== null ? number_format($classAvg, 1).'%' : '—' }}</strong></span>
            <a href="{{ route('classrooms.assignments.create', $classroom) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus-lg"></i> Assignment</a>
            <a href="{{ route('classrooms.gradebook.export', $classroom) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download"></i> Export CSV</a>
        </div>
    </div>

    <div class="gb-wrap">
        <table class="gb" aria-describedby="gbHelp">
            <thead>
                <tr>
                    <th class="stu" scope="col">Student <span class="m d-block">{{ $book->students->count() }} enrolled</span></th>
                    @foreach ($book->assignments as $a)
                        <th scope="col" title="{{ $a->title }}">
                            <span class="chip chip-{{ $a->category }}">{{ $a->category_label }}</span>
                            <a class="t" href="{{ route('classrooms.assignments.edit', [$classroom, $a]) }}">{{ $a->title }}</a>
                            <span class="m">/ {{ $fmt($a->max_points) }}@if ($a->due_date), {{ $a->due_date->format('M j') }}@endif</span>
                        </th>
                    @endforeach
                    <th class="final" scope="col">Final grade</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($book->students as $row => $s)
                @php($r = $book->results[$s->id])
                <tr data-student="{{ $s->id }}">
                    <th class="stu" scope="row">
                        <a href="{{ route('students.show', $s) }}">{{ $s->sort_name }}</a>
                        <span class="sn">{{ $s->student_number }}@if ($r['missing'])<span class="miss">, {{ $r['missing'] }} missing</span>@endif</span>
                    </th>
                    @foreach ($book->assignments as $col => $a)
                        @php($g = $book->grade($s->id, $a->id))
                        @php($name = "scores[{$s->id}][{$a->id}]")
                        @php($errKey = "scores.{$s->id}.{$a->id}")
                        @php($value = old("scores.{$s->id}.{$a->id}", $g?->excused ? 'EX' : ($g?->score !== null ? $fmt($g->score) : '')))
                        <td class="cell {{ $errors->has($errKey) ? 'invalid' : '' }}" data-assignment="{{ $a->id }}" data-past="{{ $a->isPastDue() ? 1 : 0 }}">
                            <input class="score" name="{{ $name }}" value="{{ $value }}" inputmode="decimal" autocomplete="off"
                                   data-row="{{ $row }}" data-col="{{ $col }}" data-max="{{ $a->max_points }}" data-original="{{ $value }}"
                                   aria-label="{{ $s->full_name }}, {{ $a->title }}, out of {{ $fmt($a->max_points) }}"
                                   @if ($errors->has($errKey)) aria-invalid="true" title="{{ $errors->first($errKey) }}" @endif
                                   placeholder="{{ $a->isPastDue() ? '—' : '' }}">
                        </td>
                    @endforeach
                    <td class="final"><span class="out">@include('gradebook._final', ['percent' => $r['percent'], 'letter' => $r['letter']])</span></td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th class="stu" scope="row">Assignment average</th>
                    @foreach ($book->assignments as $a)
                        <td data-avg="{{ $a->id }}">{{ $book->assignmentAverages[$a->id] !== null ? number_format($book->assignmentAverages[$a->id], 1).'%' : '—' }}</td>
                    @endforeach
                    <td class="final"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="gb-legend" id="gbHelp">
        <span>Type points, <strong>EX</strong> to excuse, or leave blank if not graded. Enter and arrow keys move between cells.</span>
        <span><span class="sw" style="background:#f1f8f1"></span>90% or higher</span>
        <span><span class="sw" style="background:#fdf0f0"></span>Below {{ config('lms.letter_grades.D') }}%</span>
        <span><span class="sw" style="background: repeating-linear-gradient(135deg, #fff 0 4px, #fbf3e2 4px 6px)"></span>Past due, no score</span>
        <span><span class="sw" style="box-shadow: inset 3px 0 0 var(--pencil)"></span>Unsaved change</span>
    </div>
</form>
@endif
@endsection

@if ($book->students->isNotEmpty() && $book->assignments->isNotEmpty())
@push('scripts')
<script>
(function () {
    const cfg = @json($book->toClientConfig());
    const form = document.getElementById('gradebook');
    const status = document.getElementById('gbStatus');
    const inputs = [...form.querySelectorAll('input.score')];
    const byId = Object.fromEntries(cfg.assignments.map((a) => [a.id, a]));
    const letterKeys = Object.keys(cfg.letters);
    let dirty = false;

    const letterFor = (p) => {
        if (p == null) return null;
        for (const l of letterKeys) if (Math.round(p * 100) / 100 >= cfg.letters[l]) return l;
        return letterKeys[letterKeys.length - 1];
    };

    // Same maths as App\Services\GradeCalculator.
    function parse(input) {
        const v = input.value.trim();
        if (v === '') return { score: null, excused: false, ok: true };
        if (v.toLowerCase() === 'ex') return { score: null, excused: true, ok: true };
        const n = Number(v);
        const max = Number(input.dataset.max);
        return { score: n, excused: false, ok: v !== '' && !Number.isNaN(n) && n >= 0 && n <= max };
    }

    function paintCell(input) {
        const td = input.parentElement;
        const p = parse(input);
        const max = Number(input.dataset.max);
        td.classList.toggle('invalid', !p.ok);
        td.classList.toggle('excused', p.excused);
        td.classList.toggle('changed', input.value.trim() !== input.dataset.original);
        td.classList.toggle('missing', td.dataset.past === '1' && p.ok && p.score === null && !p.excused);
        const pct = p.ok && p.score !== null ? p.score / max * 100 : null;
        td.classList.toggle('band-hi', pct !== null && pct >= 90);
        td.classList.toggle('band-lo', pct !== null && pct < cfg.letters.D);
        input.setAttribute('aria-invalid', p.ok ? 'false' : 'true');
        input.title = p.ok ? '' : `Enter 0–${max} points, EX for excused, or leave blank.`;
    }

    function recalcRow(tr) {
        const earned = {}, possible = {};
        tr.querySelectorAll('input.score').forEach((input) => {
            const a = byId[input.parentElement.dataset.assignment];
            const p = parse(input);
            if (!p.ok || p.excused || p.score === null) return;
            earned[a.category] = (earned[a.category] || 0) + p.score;
            possible[a.category] = (possible[a.category] || 0) + a.max;
        });
        let sum = 0, used = 0;
        for (const cat in possible) {
            const w = Number(cfg.weights[cat] || 0);
            if (w <= 0 || possible[cat] <= 0) continue;
            sum += earned[cat] / possible[cat] * 100 * w;
            used += w;
        }
        const pct = used > 0 ? sum / used : null;
        tr.dataset.pct = pct ?? '';
        const l = letterFor(pct);
        tr.querySelector('.out').innerHTML = l
            ? `<span class="grade"><span class="letter letter-${l}">${l}</span><span class="pct">${pct.toFixed(1)}%</span>${pct < cfg.atRisk ? '<span class="flag flag-critical ms-1" title="Below ' + cfg.atRisk + '%"><i class="bi bi-exclamation-circle"></i></span>' : ''}</span>`
            : '<span class="grade"><span class="letter letter-none">–</span><span class="muted">No scores</span></span>';
    }

    function recalcFooter() {
        cfg.assignments.forEach((a) => {
            const pcts = [];
            form.querySelectorAll(`td[data-assignment="${a.id}"] input`).forEach((input) => {
                const p = parse(input);
                if (p.ok && !p.excused && p.score !== null) pcts.push(p.score / a.max * 100);
            });
            form.querySelector(`[data-avg="${a.id}"]`).textContent = pcts.length ? (pcts.reduce((x, y) => x + y, 0) / pcts.length).toFixed(1) + '%' : '—';
        });
        const rows = [...form.querySelectorAll('tbody tr')].map((tr) => tr.dataset.pct).filter((v) => v !== '').map(Number);
        document.getElementById('classAvg').textContent = rows.length ? (rows.reduce((x, y) => x + y, 0) / rows.length).toFixed(1) + '%' : '—';
    }

    function setStatus() {
        const changed = form.querySelectorAll('td.changed').length;
        const invalid = form.querySelectorAll('td.invalid').length;
        dirty = changed > 0;
        status.classList.toggle('dirty', dirty || invalid > 0);
        status.textContent = invalid
            ? `${invalid} ${invalid === 1 ? 'score needs' : 'scores need'} fixing before you can save`
            : dirty ? `${changed} unsaved ${changed === 1 ? 'change' : 'changes'}` : 'All changes saved';
        document.getElementById('saveBtn').disabled = invalid > 0;
    }

    inputs.forEach((input) => {
        paintCell(input);
        input.addEventListener('input', () => {
            paintCell(input);
            recalcRow(input.closest('tr'));
            recalcFooter();
            setStatus();
        });
        input.addEventListener('focus', () => input.select());
        input.addEventListener('keydown', (e) => {
            const r = +input.dataset.row, c = +input.dataset.col;
            const moves = { ArrowUp: [-1, 0], ArrowDown: [1, 0], Enter: [e.shiftKey ? -1 : 1, 0] };
            if (e.key === 'ArrowLeft' && input.selectionStart === 0) moves.ArrowLeft = [0, -1];
            if (e.key === 'ArrowRight' && input.selectionEnd === input.value.length) moves.ArrowRight = [0, 1];
            const m = moves[e.key];
            if (!m) return;
            const next = form.querySelector(`input.score[data-row="${r + m[0]}"][data-col="${c + m[1]}"]`);
            e.preventDefault();
            next?.focus();
        });
    });
    form.querySelectorAll('tbody tr').forEach(recalcRow);
    setStatus();

    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    form.addEventListener('submit', () => { dirty = false; });

    // Ctrl/Cmd + S saves the gradebook.
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); form.requestSubmit(); }
    });

    // After a failed save, jump to the first problem.
    form.querySelector('td.invalid input')?.focus();
})();
</script>
@endpush
@endif
