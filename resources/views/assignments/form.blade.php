@extends('layouts.app')

@php($editing = $assignment->exists)
@section('title', $editing ? 'Edit '.$assignment->title : 'Add assignment')

@section('content')
<x-page-head :title="$editing ? 'Edit assignment' : 'Add an assignment'" :sub="$classroom->name"
             :crumbs="[(auth()->user()->isAdmin() ? 'Classes' : 'My classes') => route('classrooms.index'), $classroom->name => route('classrooms.show', [$classroom, 'tab' => 'assignments'])]" />

<form method="POST" action="{{ $editing ? route('classrooms.assignments.update', [$classroom, $assignment]) : route('classrooms.assignments.store', $classroom) }}"
      class="sheet" style="max-width: 760px" novalidate>
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="sheet-body">
        <div class="row">
            <x-field class="col-12" name="title" label="Title" :value="$assignment->title" required maxlength="150" placeholder="Homework 5: Linked lists" />
            <div class="col-md-4 mb-3">
                <label class="form-label" for="f_category">Category</label>
                <select class="form-select @error('category') is-invalid @enderror" id="f_category" name="category">
                    @foreach (\App\Models\Assignment::categoryOptions() as $value => $label)
                        <option value="{{ $value }}" @selected(old('category', $assignment->category) === $value)>{{ $label }} ({{ rtrim(rtrim(number_format($classroom->categoryWeights()[$value], 1), '0'), '.') }}% of grade)</option>
                    @endforeach
                </select>
                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <x-field class="col-md-4" name="max_points" label="Points possible" type="number" :value="$assignment->max_points + 0" required min="1" step="0.5" />
            <x-field class="col-md-4" name="due_date" label="Due date" type="date" :value="$assignment->due_date?->format('Y-m-d')" />
            <x-field class="col-12" name="description" label="Instructions" type="textarea" :value="$assignment->description" />
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 px-3 pb-3 px-md-4">
        <button class="btn btn-primary" type="submit">{{ $editing ? 'Save assignment' : 'Add assignment' }}</button>
        <a class="btn btn-outline-secondary" href="{{ route('classrooms.show', [$classroom, 'tab' => 'assignments']) }}">Cancel</a>
    </div>
</form>

@if ($editing)
    <form method="POST" action="{{ route('classrooms.assignments.destroy', [$classroom, $assignment]) }}" class="mt-4"
          data-confirm="Delete “{{ $assignment->title }}”? Every score entered for it will be deleted.">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Delete assignment</button>
    </form>
@endif
@endsection
