@extends('layouts.app', ['title' => 'My Profile'])

@section('content')
    <h2>My Profile</h2>
    <p class="muted">Update your account information and profile picture.</p>

    <form method="POST" action="{{ route('profile.update') }}" class="card" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div>
                <label for="name">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
            </div>

            <div>
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>

            <div>
                <label for="phone">Phone</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}">
            </div>

            <div>
                <label for="password">New Password (Optional)</label>
                <div class="password-input-wrap">
                    <input id="password" type="password" name="password">
                    <button type="button" class="password-toggle" data-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false">Show</button>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 0.8rem;">
            <label for="profile_photo">Profile Picture</label>
            <input id="profile_photo" type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp">
            <p class="muted" style="margin: 0.35rem 0 0; font-size: 0.88rem;">PNG, JPG or WEBP up to 2MB.</p>
        </div>

        @if($user->profile_photo_path)
            <div style="margin-bottom: 0.8rem;">
                <div class="muted" style="margin-bottom: 0.4rem;">Current Profile Picture</div>
                <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="{{ $user->name }} profile photo" class="user-photo-lg">
            </div>

            <div style="margin-bottom: 1rem; display:flex; align-items:center; gap:0.5rem;">
                <input id="remove_profile_photo" type="checkbox" name="remove_profile_photo" value="1" style="width:auto;" @checked(old('remove_profile_photo'))>
                <label for="remove_profile_photo">Remove current profile picture</label>
            </div>
        @endif

        <button type="submit">Save Profile</button>
    </form>

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
