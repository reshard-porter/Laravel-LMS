@extends('layouts.app')

@php($editing = $student->exists)
@section('title', $editing ? 'Edit '.$student->full_name : 'Add student')

@section('content')
<x-page-head :title="$editing ? 'Edit '.$student->full_name : 'Add a student'"
             :crumbs="$editing ? ['Students' => route('students.index'), $student->full_name => route('students.show', $student)] : ['Students' => route('students.index')]" />

<form method="POST" action="{{ $editing ? route('students.update', $student) : route('students.store') }}" class="sheet" style="max-width: 760px" novalidate>
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="sheet-body">
        <div class="form-section">
            <h2>Student</h2>
            <div class="row">
                <x-field class="col-md-6" name="first_name" label="First name" :value="$student->first_name" required maxlength="100" />
                <x-field class="col-md-6" name="last_name" label="Last name" :value="$student->last_name" required maxlength="100" />
                <x-field class="col-md-4" name="student_number" label="Student number" :value="$student->student_number" required maxlength="20" />
                <x-field class="col-md-4" name="date_of_birth" label="Date of birth" type="date" :value="$student->date_of_birth?->format('Y-m-d')" />
                <x-field class="col-md-4" name="year_level" label="Year" type="number" :value="$student->year_level" required min="1" max="12" />
                <x-field class="col-md-8" name="email" label="Email" type="email" :value="$student->email" />
                <div class="col-md-4 mb-3">
                    <label for="f_status" class="form-label">Status</label>
                    <select id="f_status" name="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach (\App\Models\Student::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $student->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <div class="form-section">
            <h2>Parent or guardian</h2>
            <div class="row">
                <x-field class="col-md-6" name="guardian_name" label="Name" :value="$student->guardian_name" />
                <x-field class="col-md-6" name="guardian_phone" label="Phone" :value="$student->guardian_phone" />
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 px-3 pb-3 px-md-4">
        <button class="btn btn-primary" type="submit">{{ $editing ? 'Save changes' : 'Add student' }}</button>
        <a class="btn btn-outline-secondary" href="{{ $editing ? route('students.show', $student) : route('students.index') }}">Cancel</a>
    </div>
</form>
@endsection
