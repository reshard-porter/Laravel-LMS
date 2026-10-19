{{-- Shared header for a class's pages: overview, gradebook, attendance. --}}
<x-page-head :title="$classroom->name"
             :sub="collect([$classroom->code, $classroom->teacher?->full_name ?? 'No teacher assigned', $classroom->schedule, $classroom->room])->filter()->implode(', ')"
             :crumbs="[(auth()->user()->isAdmin() ? 'Classes' : 'My classes') => route('classrooms.index')]">
    <a href="{{ route('classrooms.show', $classroom) }}" class="btn {{ ($active ?? '') === 'overview' ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="bi bi-easel"></i> Overview</a>
    <a href="{{ route('classrooms.gradebook', $classroom) }}" class="btn {{ ($active ?? '') === 'gradebook' ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="bi bi-journal-check"></i> Gradebook</a>
    <a href="{{ route('classrooms.attendance', $classroom) }}" class="btn {{ ($active ?? '') === 'attendance' ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="bi bi-calendar-check"></i> Attendance</a>
    <a href="{{ route('classrooms.edit', $classroom) }}" class="btn btn-outline-secondary" title="Class settings"><i class="bi bi-sliders"></i><span class="visually-hidden">Class settings</span></a>
</x-page-head>
