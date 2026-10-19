@extends('layouts.app')

@section('title', 'Attendance: '.$classroom->name)

@php($statuses = \App\Models\AttendanceRecord::statusOptions())

@section('content')
@include('classrooms._header', ['active' => 'attendance'])

<div class="row g-3">
    <div class="col-xl-8">
        <form method="POST" action="{{ route('classrooms.attendance.update', $classroom) }}" class="sheet" id="attForm">
            @csrf @method('PUT')
            <div class="gb-toolbar flex-wrap">
                <div>
                    <label for="attDate" class="form-label mb-0 small-muted">Class date</label>
                    <input type="date" id="attDate" name="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}"
                           class="form-control form-control-sm" onchange="location.href='{{ route('classrooms.attendance', $classroom) }}?date='+this.value">
                </div>
                <div class="small-muted align-self-end">
                    @if ($taken)
                        <span class="flag flag-good"><i class="bi bi-check2"></i>Taken</span> You can still change it.
                    @else
                        <span class="flag flag-neutral">Not taken yet</span> Everyone starts as present.
                    @endif
                </div>
                <div class="ms-auto align-self-end d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="allPresent">Mark everyone present</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check2"></i> Save attendance</button>
                </div>
            </div>
            @error('date')<div class="alert alert-danger m-3 py-2">{{ $message }}</div>@enderror

            @if ($students->isEmpty())
                <div class="empty"><i class="bi bi-people"></i>No students enrolled yet.</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <tbody>
                        @foreach ($students as $s)
                            @php($current = old("statuses.{$s->id}", $records[$s->id] ?? 'present'))
                            <tr>
                                <th scope="row" class="first fw-semibold">{{ $s->sort_name }} <span class="d-block small-muted fw-normal">{{ $s->student_number }}</span></th>
                                <td class="last">
                                    <div class="att-row" role="radiogroup" aria-label="Attendance for {{ $s->full_name }}">
                                        @foreach ($statuses as $value => $label)
                                            <input type="radio" class="btn-check" name="statuses[{{ $s->id }}]" id="a{{ $s->id }}{{ $value }}" value="{{ $value }}" @checked($current === $value)>
                                            <label class="btn btn-sm btn-outline-secondary att-{{ $value }}" for="a{{ $s->id }}{{ $value }}">{{ $label }}</label>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </form>
    </div>

    <div class="col-xl-4">
        <section class="sheet">
            <div class="sheet-head"><h2>Recent class days</h2></div>
            <div class="sheet-flush">
                @forelse ($recent as $day)
                    <a href="{{ route('classrooms.attendance', [$classroom, 'date' => $day['date']->toDateString()]) }}"
                       class="d-flex align-items-center gap-3 px-3 py-2 text-decoration-none text-body border-top {{ $day['date']->isSameDay($date) ? 'fw-semibold' : '' }}"
                       style="border-color: var(--line-2) !important">
                        <span style="min-width: 7.5rem">{{ $day['date']->format('D, M j') }}</span>
                        <div class="meter flex-grow-1" aria-hidden="true"><span style="width: {{ $day['rate'] ?? 0 }}%"></span></div>
                        <span class="text-nowrap" style="min-width: 3rem; text-align: right">{{ $day['rate'] !== null ? number_format($day['rate'], 0).'%' : '—' }}</span>
                        @if (($day['counts']['absent'] ?? 0) > 0)
                            <span class="flag flag-critical" title="Absent">{{ $day['counts']['absent'] }} absent</span>
                        @endif
                    </a>
                @empty
                    <div class="empty"><i class="bi bi-calendar"></i>No attendance recorded yet.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('allPresent')?.addEventListener('click', () => {
    document.querySelectorAll('#attForm input[type=radio][value=present]').forEach((r) => { r.checked = true; });
});
</script>
@endpush
