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

    <form method="POST" action="{{ route('admin.settings.reset-data') }}" class="card" style="margin-top: 1rem; border-color: #f2c4c4;">
        @csrf

        <h3 style="margin-top: 0; color: #7a1e1e;">Reset Operational Data</h3>
        <p class="muted" style="margin-top: 0;">This will clear tickets, ticket attachments, cost requests (through ticket cleanup), audit logs, and application log files. Maintenance categories will remain.</p>

        <div style="margin-bottom: 1rem; display:flex; align-items:center; gap:0.5rem;">
            <input id="confirm_reset" type="checkbox" name="confirm_reset" value="1" style="width:auto;" required>
            <label for="confirm_reset">I confirm I want to reset operational data.</label>
        </div>

        <button
            type="submit"
            style="background:#b42318;"
            onclick="return confirm('Reset tickets and logs now? Categories will be preserved.');"
        >
            Reset Data
        </button>
    </form>
@endsection
