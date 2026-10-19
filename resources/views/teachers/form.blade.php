@extends('layouts.app')

@php($editing = $teacher->exists)
@section('title', $editing ? 'Edit '.$teacher->full_name : 'Add teacher')

@section('content')
<x-page-head :title="$editing ? 'Edit '.$teacher->full_name : 'Add a teacher'"
             :crumbs="$editing ? ['Teachers' => route('teachers.index'), $teacher->full_name => route('teachers.show', $teacher)] : ['Teachers' => route('teachers.index')]" />

<form method="POST" action="{{ $editing ? route('teachers.update', $teacher) : route('teachers.store') }}" class="sheet" style="max-width: 760px" novalidate>
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="sheet-body">
        <div class="form-section">
            <h2>Details</h2>
            <div class="row">
                <x-field class="col-md-6" name="first_name" label="First name" :value="$teacher->first_name" required maxlength="100" />
                <x-field class="col-md-6" name="last_name" label="Last name" :value="$teacher->last_name" required maxlength="100" />
                <x-field class="col-md-6" name="email" label="Email" type="email" :value="$teacher->email" required />
                <x-field class="col-md-6" name="phone" label="Phone" :value="$teacher->phone" />
                <x-field class="col-md-6" name="department" label="Department" :value="$teacher->department" required list="departments" />
                <x-field class="col-md-3" name="hire_date" label="Hire date" type="date" :value="$teacher->hire_date?->format('Y-m-d')" />
                <x-field class="col-md-3" name="salary" label="Annual salary" type="number" :value="$teacher->salary" required min="0" step="1" />
            </div>
            <datalist id="departments">
                @foreach (\App\Models\Teacher::distinct()->orderBy('department')->pluck('department') as $d)
                    <option value="{{ $d }}"></option>
                @endforeach
            </datalist>
        </div>
        <div class="form-section">
            <h2>Sign-in</h2>
            <x-field name="login_password" label="{{ $teacher->user ? 'New password' : 'Password' }}" type="password" autocomplete="new-password"
                     :help="$teacher->user ? 'They sign in with the email above. Leave blank to keep their current password.' : 'Set a password so this teacher can sign in and manage their own classes. Leave blank to skip for now.'" />
        </div>
    </div>
    <div class="d-flex gap-2 px-3 pb-3 px-md-4">
        <button class="btn btn-primary" type="submit">{{ $editing ? 'Save changes' : 'Add teacher' }}</button>
        <a class="btn btn-outline-secondary" href="{{ $editing ? route('teachers.show', $teacher) : route('teachers.index') }}">Cancel</a>
    </div>
</form>
@endsection
