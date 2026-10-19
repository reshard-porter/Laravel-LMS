@extends('layouts.app')

@section('title', 'Account')

@section('content')
<x-page-head title="Your account" :sub="$user->name.', '.($user->isAdmin() ? 'administrator' : 'teacher')" />

<div class="row g-3">
    <div class="col-lg-6">
        <form method="POST" action="{{ route('account.password') }}" class="sheet" novalidate>
            @csrf @method('PUT')
            <div class="sheet-head"><h2>Change password</h2></div>
            <div class="sheet-body">
                <p class="muted">You sign in as <strong class="text-body">{{ $user->email }}</strong>.</p>
                <x-field name="current_password" label="Current password" type="password" required autocomplete="current-password" />
                <x-field name="password" label="New password" type="password" required autocomplete="new-password" help="At least 8 characters." />
                <x-field name="password_confirmation" label="Repeat new password" type="password" required autocomplete="new-password" />
                <button class="btn btn-primary" type="submit">Update password</button>
            </div>
        </form>
    </div>
</div>
@endsection
