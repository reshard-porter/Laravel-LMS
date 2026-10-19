@extends('layouts.app')

@php($editing = $classroom->exists)
@php($weights = $classroom->categoryWeights())
@php($custom = old('custom_weights', $classroom->usesCustomWeights() ? '1' : '0') == '1')
@section('title', $editing ? $classroom->name.' settings' : 'New class')

@section('content')
<x-page-head :title="$editing ? 'Class settings' : 'Create a class'"
             :sub="$editing ? $classroom->name : null"
             :crumbs="$editing ? [(auth()->user()->isAdmin() ? 'Classes' : 'My classes') => route('classrooms.index'), $classroom->name => route('classrooms.show', $classroom)] : ['Classes' => route('classrooms.index')]" />

<form method="POST" action="{{ $editing ? route('classrooms.update', $classroom) : route('classrooms.store') }}" class="sheet" style="max-width: 820px" novalidate>
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="sheet-body">
        <div class="form-section">
            <h2>Class</h2>
            <div class="row">
                <x-field class="col-md-8" name="name" label="Class name" :value="$classroom->name" required maxlength="150" />
                <x-field class="col-md-4" name="code" label="Code" :value="$classroom->code" required maxlength="20" help="Letters, numbers and dashes, e.g. CS201" />
                <x-field class="col-md-6" name="subject" label="Subject" :value="$classroom->subject" required maxlength="100" />
                <div class="col-md-6 mb-3">
                    <label for="f_teacher" class="form-label">Class teacher <span class="muted fw-normal">(optional)</span></label>
                    <select id="f_teacher" name="teacher_id" class="form-select @error('teacher_id') is-invalid @enderror" @cannot('admin') disabled @endcannot>
                        <option value="">Unassigned</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}" @selected(old('teacher_id', $classroom->teacher_id) == $t->id)>{{ $t->full_name }}, {{ $t->department }}</option>
                        @endforeach
                    </select>
                    @cannot('admin')<div class="form-text">Only an administrator can change the class teacher.</div>@endcannot
                    @error('teacher_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <x-field class="col-md-4" name="term" label="Term" :value="$classroom->term" required maxlength="50" />
                <x-field class="col-md-4" name="schedule" label="Meets" :value="$classroom->schedule" placeholder="Mon & Wed, 9:00–10:30" />
                <x-field class="col-md-2" name="room" label="Room" :value="$classroom->room" />
                <x-field class="col-md-2" name="capacity" label="Seats" type="number" :value="$classroom->capacity" required min="1" max="500" />
                <x-field class="col-12" name="description" label="Description" type="textarea" :value="$classroom->description" maxlength="2000" />
            </div>
        </div>

        <div class="form-section">
            <h2>Grade weights</h2>
            <p class="muted" style="max-width: 64ch">The final grade combines each category’s percentage using these weights. Categories with no graded work yet are left out until they have scores.</p>
            <div class="form-check form-switch mb-3">
                <input type="hidden" name="custom_weights" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="customWeights" name="custom_weights" value="1" @checked($custom)>
                <label class="form-check-label" for="customWeights">Use custom weights for this class</label>
            </div>
            @error('weights')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
            <div class="row g-2" id="weightFields">
                @foreach (config('lms.categories') as $key => $cat)
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="w_{{ $key }}">{{ $cat['label'] }}</label>
                        <div class="input-group">
                            <input class="form-control weight" type="number" min="0" max="100" step="0.5" id="w_{{ $key }}" name="weights[{{ $key }}]"
                                   value="{{ old("weights.$key", $weights[$key]) }}" data-default="{{ $cat['weight'] }}">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 small" id="weightTotal" aria-live="polite"></div>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 px-3 pb-3 px-md-4">
        <button class="btn btn-primary" type="submit">{{ $editing ? 'Save settings' : 'Create class' }}</button>
        <a class="btn btn-outline-secondary" href="{{ $editing ? route('classrooms.show', $classroom) : route('classrooms.index') }}">Cancel</a>
    </div>
</form>

@if ($editing)
    @can('admin')
        <form method="POST" action="{{ route('classrooms.destroy', $classroom) }}" class="mt-4" style="max-width: 820px"
              data-confirm="Delete {{ $classroom->name }}? Its assignments, scores and attendance will be deleted. This can’t be undone.">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Delete this class</button>
        </form>
    @endcan
@endif
@endsection

@push('scripts')
<script>
(function () {
    const toggle = document.getElementById('customWeights');
    const inputs = [...document.querySelectorAll('.weight')];
    const total = document.getElementById('weightTotal');
    function update() {
        inputs.forEach((i) => {
            i.disabled = !toggle.checked;
            if (!toggle.checked) i.value = i.dataset.default;
        });
        const sum = inputs.reduce((s, i) => s + (parseFloat(i.value) || 0), 0);
        const ok = Math.abs(sum - 100) < 0.01;
        total.textContent = toggle.checked
            ? (ok ? 'Weights add up to 100%.' : `Weights add up to ${+sum.toFixed(2)}%. They need to add up to 100%.`)
            : 'Using the school’s default weights.';
        total.className = 'mt-2 small ' + (toggle.checked && !ok ? 'text-danger fw-semibold' : 'muted');
    }
    toggle.addEventListener('change', update);
    inputs.forEach((i) => i.addEventListener('input', update));
    update();
})();
</script>
@endpush
