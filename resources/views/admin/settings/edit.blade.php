@extends('layouts.app', ['title' => 'Settings'])

@section('content')
    <h2>Application Settings</h2>
    <p class="muted">Manage site branding and logo.</p>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="card" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div style="margin-bottom: 0.8rem;">
            <label for="site_name">Site Name</label>
            <input id="site_name" type="text" name="site_name" value="{{ old('site_name', $siteName) }}" required>
        </div>

        <div style="margin-bottom: 0.8rem;">
            <label for="logo">Upload Logo</label>
            <input id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp">
            <p class="muted" style="margin: 0.35rem 0 0; font-size: 0.88rem;">PNG, JPG, or WEBP up to 2MB.</p>
        </div>

        @if($logoPath)
            <div style="margin-bottom: 0.8rem;">
                <div class="muted" style="margin-bottom: 0.4rem;">Current Logo</div>
                <img src="{{ asset('storage/'.$logoPath) }}" alt="Current logo" style="max-height: 72px; width: auto; display: block; background: #fff; border: 1px solid var(--border); border-radius: 8px; padding: 0.4rem;">
            </div>

            <div style="margin-bottom: 1rem; display:flex; align-items:center; gap:0.5rem;">
                <input id="remove_logo" type="checkbox" name="remove_logo" value="1" style="width:auto;" @checked(old('remove_logo'))>
                <label for="remove_logo">Remove current logo</label>
            </div>
        @endif

        <button type="submit">Save Settings</button>
    </form>
@endsection
