@extends('layouts.app')

@section('title', 'Teachers')

@section('content')
<x-page-head title="Teachers" :sub="$teachers->total().' on staff'">
    @can('admin')
        <a href="{{ route('teachers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add teacher</a>
    @endcan
</x-page-head>

<div class="sheet">
    <form class="p-3 border-bottom" method="GET" role="search" style="border-color: var(--line-2) !important">
        <div class="search" style="max-width: 360px">
            <i class="bi bi-search"></i>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by name, email or department" aria-label="Search teachers">
        </div>
    </form>

    @if ($teachers->isEmpty())
        <div class="empty">
            <i class="bi bi-person-badge"></i>
            @if (request('q'))
                No teachers match “{{ request('q') }}”. <a href="{{ route('teachers.index') }}">Clear the search</a>.
            @else
                No teachers yet. @can('admin')<a href="{{ route('teachers.create') }}">Add the first one</a>.@endcan
            @endif
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr>
                    <th class="first">Name</th><th>Department</th><th>Email</th><th class="num">Classes</th>
                    @can('admin')<th class="num">Salary</th>@endcan
                    <th class="last">Sign-in</th>
                </tr></thead>
                <tbody>
                @foreach ($teachers as $t)
                    <tr>
                        <td class="first"><a class="row-link" href="{{ route('teachers.show', $t) }}">{{ $t->full_name }}</a></td>
                        <td>{{ $t->department }}</td>
                        <td><a href="mailto:{{ $t->email }}" class="text-decoration-none">{{ $t->email }}</a></td>
                        <td class="num">{{ $t->classrooms_count }}</td>
                        @can('admin')<td class="num">{{ number_format($t->salary) }}</td>@endcan
                        <td class="last">
                            @if ($t->user_id)
                                <span class="flag flag-good"><i class="bi bi-key"></i>Can sign in</span>
                            @else
                                <span class="flag flag-neutral">No account</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($teachers->hasPages())<div class="p-3">{{ $teachers->links() }}</div>@endif
    @endif
</div>
@endsection
