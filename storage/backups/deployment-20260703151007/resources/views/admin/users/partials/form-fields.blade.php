<div class="form-grid">
    <div>
        <label for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required>
    </div>
    <div>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
    </div>
    <div>
        <label for="phone">Phone</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}">
    </div>
    <div>
        <label for="role">Role</label>
        <select id="role" name="role" required>
            @foreach($roles as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role ?? '') === $role)>
                    {{ $role === \App\Models\User::ROLE_ADMIN ? 'Super Admin' : str($role)->replace('_', ' ')->title() }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div style="margin-bottom: 1rem;">
    <label for="password">Password @if(isset($user))(Leave blank to keep current password)@endif</label>
    <div class="password-input-wrap">
        <input id="password" type="password" name="password" @if(!isset($user))required @endif>
        <button type="button" class="password-toggle" data-user-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false">Show</button>
    </div>
</div>

<div style="margin-bottom: 0.8rem;">
    <label for="profile_photo">Profile Picture</label>
    <input id="profile_photo" type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp">
    <p class="muted" style="margin: 0.35rem 0 0; font-size: 0.88rem;">PNG, JPG or WEBP up to 2MB.</p>
</div>

@if(isset($user) && $user->profile_photo_path)
    <div style="margin-bottom: 0.8rem;">
        <div class="muted" style="margin-bottom: 0.4rem;">Current Profile Picture</div>
        <img src="{{ route('media.show', ['path' => $user->profile_photo_path]) }}" alt="{{ $user->name }} profile photo" class="user-photo-lg">
    </div>

    <div style="margin-bottom: 1rem; display:flex; align-items:center; gap:0.5rem;">
        <input id="remove_profile_photo" type="checkbox" name="remove_profile_photo" value="1" style="width:auto;" @checked(old('remove_profile_photo'))>
        <label for="remove_profile_photo">Remove current profile picture</label>
    </div>
@endif

<script>
    (function () {
        const toggle = document.querySelector('[data-user-password-toggle]');
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
