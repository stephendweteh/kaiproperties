@extends('layouts.app', ['title' => 'Super Admin Login'])

@section('content')
    @php
        $siteName = \App\Models\Setting::valueFor('site_name', 'Kai Properties');
        $siteLogoPath = \App\Models\Setting::valueFor('logo_path');
    @endphp

    <section class="login-shell">
        <article class="login-showcase">
            @if($siteLogoPath)
                <img src="{{ asset('storage/'.$siteLogoPath) }}" alt="{{ $siteName }} logo" class="login-showcase-logo">
            @else
                <div class="login-showcase-logo-fallback">{{ strtoupper(substr($siteName, 0, 1)) }}</div>
            @endif

            <p class="login-kicker">Kai Properties Ltd</p>
            <h2>Operations Made Simple</h2>
        </article>

        <article class="login-card-modern">
            <div class="login-brand-top">
                @if($siteLogoPath)
                    <img src="{{ asset('storage/'.$siteLogoPath) }}" alt="{{ $siteName }} logo" class="login-top-logo">
                @else
                    <div class="login-top-logo-fallback">{{ strtoupper(substr($siteName, 0, 1)) }}</div>
                @endif
                <div class="login-brand-name">{{ $siteName }}</div>
            </div>

            <div class="login-form-wrap">
                <h3>Sign In</h3>
                <p class="muted">Use your account credentials to access the platform.</p>

                <form method="POST" action="{{ route('login.attempt') }}" class="login-form">
                    @csrf

                    <div class="field-group">
                        <label for="email">Email Address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="name@company.com" required>
                    </div>

                    <div class="field-group">
                        <label for="password">Password</label>
                        <div class="password-input-wrap">
                            <input id="password" type="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" data-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false">Show</button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">Login</button>
                </form>
            </div>
        </article>
    </section>

    <script>
        (function () {
            const toggle = document.querySelector('[data-password-toggle]');
            const input = document.getElementById('password');

            if (!toggle || !input) {
                return;
            }

            toggle.addEventListener('click', function () {
                const isHidden = input.type === 'password';

                input.type = isHidden ? 'text' : 'password';
                toggle.textContent = isHidden ? 'Hide' : 'Show';
                toggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                toggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            });
        })();
    </script>
@endsection
