@extends('layouts.app')
@section('title','Admin sign in')
@section('content')
<link rel="stylesheet" href="{{ asset('admin-workspace.css') }}">
<main class="auth-page aw-login"><span class="eyebrow muted">STORE MANAGEMENT</span><h1>Admin sign in</h1><p>Sign in with your administrator or staff account.</p>
<form action="/admin/login" method="post" class="panel-form">@csrf
<label for="admin-email">Email</label><input id="admin-email" name="email" type="email" required maxlength="190" autocomplete="username" value="{{ old('email') }}" aria-invalid="{{ $errors->has('email')?'true':'false' }}" aria-describedby="admin-email-error">@error('email')<span id="admin-email-error" class="aw-inline-error" role="alert">{{ $message }}</span>@enderror
<label for="admin-password">Password</label><div class="aw-login-password"><input id="admin-password" name="password" type="password" required autocomplete="current-password"><button id="show-admin-password" type="button" aria-controls="admin-password" aria-pressed="false">Show</button></div>@error('password')<span class="aw-inline-error" role="alert">{{ $message }}</span>@enderror
<span id="admin-caps" class="aw-caps" hidden>Caps Lock is on.</span><button class="primary full">Sign in as admin</button>
</form><div class="auth-links"><a href="/password/reset">Forgot password?</a><a href="/login">Customer sign in</a></div></main>
@endsection
@section('scripts')<script src="{{ asset('admin-workspace.js') }}" defer></script>@endsection