@extends('layouts.app', ['title' => 'Kai Property Ltd'])

@section('content')
    @php
        $siteName = \App\Models\Setting::valueFor('site_name', 'Kai Properties');
        $siteLogoPath = \App\Models\Setting::valueFor('logo_path');
    @endphp

    <section class="login-shell">
        <article class="login-showcase">
            @if($siteLogoPath)
                <img src="{{ route('media.show', ['path' => $siteLogoPath]) }}" alt="{{ $siteName }} logo" class="login-showcase-logo">
            @else
                <div class="login-showcase-logo-fallback">{{ strtoupper(substr($siteName, 0, 1)) }}</div>
            @endif

            <p class="login-kicker">Kai Properties Ltd</p>
            <h2>Operations Made Simple</h2>
        </article>

        <article class="login-card-modern">
            <div class="login-brand-top">
                @if($siteLogoPath)
                    <img src="{{ route('media.show', ['path' => $siteLogoPath]) }}" alt="{{ $siteName }} logo" class="login-top-logo">
                @else
                    <div class="login-top-logo-fallback">{{ strtoupper(substr($siteName, 0, 1)) }}</div>
                @endif
                <div class="login-brand-name">{{ $siteName }}</div>
            </div>

            <div class="login-form-wrap">
                <h3>Sign In</h3>
                <p class="muted">Use your account credentials to access the platform.</p>

                <form method="POST" action="{{ route('login.attempt') }}" class="login-form" data-loader-action="login">
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

                    <button type="submit" class="btn-login" data-loader-action="login">Login</button>
                </form>

                <div class="card" style="margin-top: 1rem; background:#f8fafc; border:1px solid #e2e8f0; box-shadow:none;">
                    <h4 style="margin:0 0 0.35rem;">Need an account?</h4>
                    <p class="muted" style="margin:0 0 0.8rem;">Request access. All requests go to Operations Manager.</p>
                    <a class="btn" href="{{ route('signup.show') }}" style="display:inline-block; text-decoration:none;">Request Access</a>
                </div>
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
