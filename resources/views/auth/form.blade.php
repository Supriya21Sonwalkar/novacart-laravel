
@extends('layouts.app')

@section('title', ucfirst($mode))

@section('content')
    <main class="auth-page">
        <span class="eyebrow muted">WELCOME TO {{ strtoupper($store['name'] ?? 'NOVACART') }}</span>

        <h1>
            {{ ['login' => 'Good to see you.', 'register' => 'Make yourself at home.', 'forgot' => 'Reset your password.', 'reset' => 'Choose a new password.'][$mode] }}
        </h1>

        @if($mode === 'login')
            <p class="small muted">
                After signing in, complete your profile and delivery address before adding products or placing an order.
            </p>
        @endif

        <form class="panel-form" method="post"
            action="{{ ['login' => '/login', 'register' => '/register', 'forgot' => '/password/email', 'reset' => '/password/reset'][$mode] }}">

            @csrf

            @if($mode === 'reset')
                <input type="hidden" name="token" value="{{ $token }}">
            @endif

            @if($mode === 'register')
                <p class="notice">
                    Complete your contact details and delivery address to start shopping. All starred fields are required.
                </p>
                @include('shop.profile-fields')
            @else
                <label>
                    Email
                    <input type="email" name="email" required autocomplete="email"
                        value="{{ old('email', $email ?? '') }}">
                </label>
            @endif

            @if(in_array($mode, ['login', 'register', 'reset']))
                <label>
                    Password

                    @if($mode !== 'login')
                        <small>
                            8–72 characters, including uppercase, lowercase and a number.
                        </small>
                    @endif

                    <div class="password-toggle-wrapper" style="position: relative;">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            minlength="8"
                            maxlength="72"
                            @if($mode !== 'login')
                                pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,72}"
                            @endif
                            required
                            autocomplete="{{ $mode === 'login' ? 'current-password' : 'new-password' }}"
                            style="padding-right: 48px;"
                        >

                        <button
                            type="button"
                            class="password-toggle-button"
                            aria-label="Show password"
                            aria-pressed="false"
                            onclick="togglePasswordVisibility('password', this)"
                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; padding: 5px; display: flex; align-items: center; justify-content: center; color: #64748b;"
                        >
                            <svg class="password-eye-icon" xmlns="http://www.w3.org/2000/svg"
                                width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                aria-hidden="true">
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </label>
            @endif

            @if(in_array($mode, ['register', 'reset']))
                <label>
                    Confirm password

                    <div class="password-toggle-wrapper" style="position: relative;">
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            minlength="8"
                            maxlength="72"
                            required
                            autocomplete="new-password"
                            style="padding-right: 48px;"
                        >

                        <button
                            type="button"
                            class="password-toggle-button"
                            aria-label="Show password"
                            aria-pressed="false"
                            onclick="togglePasswordVisibility('password_confirmation', this)"
                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; padding: 5px; display: flex; align-items: center; justify-content: center; color: #64748b;"
                        >
                            <svg class="password-eye-icon" xmlns="http://www.w3.org/2000/svg"
                                width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                aria-hidden="true">
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </label>
            @endif

            @if($mode === 'login')
                <label class="check-label">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>
            @endif

            <button class="primary full">
                {{ ['login' => 'Sign in', 'register' => 'Create account', 'forgot' => 'Send reset link', 'reset' => 'Reset password'][$mode] }}
            </button>
        </form>

        <div class="auth-links">
            @if($mode === 'login')
                <a href="/password/reset">Forgot password?</a>
                <a href="/register">Create an account</a>
            @else
                <a href="/login">Back to sign in</a>
            @endif
        </div>

        @if(in_array($mode, ['register', 'forgot']))
            <p class="small muted">
                Demo email uses the log mail driver. Verification and reset links are written to the local
                Laravel log until SMTP is configured.
            </p>
        @endif
    </main>

    <script>
        function togglePasswordVisibility(inputId, button) {
            const passwordInput = document.getElementById(inputId);

            if (!passwordInput) {
                return;
            }

            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';

            button.setAttribute(
                'aria-label',
                isHidden ? 'Hide password' : 'Show password'
            );

            button.setAttribute('aria-pressed', String(isHidden));

            const icon = button.querySelector('.password-eye-icon');

            if (icon) {
                icon.innerHTML = isHidden
                    ? '<path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 002.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0112 5c6.4 0 10 7 10 7a14 14 0 01-4 4.7"/><path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1 0 2-.2 2.8-.5"/>'
                    : '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
            }
        }
    </script>
@endsection
