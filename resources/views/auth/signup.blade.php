@extends('layouts.app', ['title' => 'Request Access'])

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
            <h2>Request Platform Access</h2>
            <p class="muted" style="color:#dbeafe; max-width: 28ch;">Your request is reviewed by operations management before sign-in access is activated.</p>
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
                <h3>Sign Up Request</h3>
                <p class="muted">Request access. All requests go to Operations Manager.</p>

                <form method="POST" action="{{ route('signup') }}" class="login-form">
                    @csrf

                    <div class="field-group">
                        <label for="name">Full Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Your full name" required>
                    </div>

                    <div class="field-group">
                        <label for="email">Email Address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" required>
                    </div>

                    <div class="field-group">
                        <label for="phone">Phone (optional)</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}" placeholder="+233...">
                    </div>

                    <div class="field-group">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" placeholder="At least 8 characters" required>
                    </div>

                    <div class="field-group">
                        <label for="password_confirmation">Confirm Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Repeat password" required>
                    </div>

                    <button type="submit" class="btn-login">Submit Request</button>
                </form>

                <p class="muted" style="margin: 0.9rem 0 0;">
                    Already have an approved account?
                    <a href="{{ route('login') }}">Go to login</a>
                </p>
            </div>
        </article>
    </section>
@endsection
