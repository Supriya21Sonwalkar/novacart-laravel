@extends('layouts.admin')
@section('admin_title','My profile & security')
@section('admin_content')
<div class="aw-grid"><section class="aw-panel"><h2>My store account</h2><p>Update your own sign-in details. Roles and permissions are managed separately.</p><form method="post" action="{{ route('admin.team-profile.save') }}">@csrf
<label for="name">Name</label><input id="name" name="name" value="{{ old('name',auth()->user()->name) }}" maxlength="100" required>
<label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email',auth()->user()->email) }}" maxlength="190" required>
<button class="primary">Save profile</button></form></section><section class="aw-panel"><h2>Change password</h2><form method="post" action="{{ route('admin.team-profile.password') }}">@csrf
<label for="current_password">Current password</label><input id="current_password" type="password" name="current_password" autocomplete="current-password" required>
<label for="password">New password</label><input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
<label for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required><button class="primary">Change password</button></form></section></div>
@endsection
