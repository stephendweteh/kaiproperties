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

        <hr style="margin: 1rem 0; border: 0; border-top: 1px solid var(--border);">

        <h3 style="margin-top: 0;">SMTP Notification Settings</h3>

        <div class="form-grid" style="margin-bottom: 0.8rem;">
            <div>
                <label for="smtp_host">SMTP Host</label>
                <input id="smtp_host" type="text" name="smtp_host" value="{{ old('smtp_host', $smtpHost) }}" placeholder="smtp.mailprovider.com">
            </div>

            <div>
                <label for="smtp_port">SMTP Port</label>
                <input id="smtp_port" type="number" name="smtp_port" value="{{ old('smtp_port', $smtpPort) }}" min="1" max="65535" placeholder="587">
            </div>

            <div>
                <label for="smtp_username">SMTP Username</label>
                <input id="smtp_username" type="text" name="smtp_username" value="{{ old('smtp_username', $smtpUsername) }}">
            </div>

            <div>
                <label for="smtp_password">SMTP Password</label>
                <input id="smtp_password" type="password" name="smtp_password" value="{{ old('smtp_password', $smtpPassword) }}">
            </div>

            <div>
                <label for="smtp_encryption">Encryption</label>
                <select id="smtp_encryption" name="smtp_encryption">
                    <option value="">Select Encryption</option>
                    <option value="tls" @selected(old('smtp_encryption', $smtpEncryption) === 'tls')>TLS</option>
                    <option value="ssl" @selected(old('smtp_encryption', $smtpEncryption) === 'ssl')>SSL</option>
                    <option value="none" @selected(old('smtp_encryption', $smtpEncryption) === 'none')>None</option>
                </select>
            </div>

            <div>
                <label for="smtp_from_email">From Email</label>
                <input id="smtp_from_email" type="email" name="smtp_from_email" value="{{ old('smtp_from_email', $smtpFromEmail) }}" placeholder="no-reply@kai.local">
            </div>

            <div>
                <label for="smtp_from_name">From Name</label>
                <input id="smtp_from_name" type="text" name="smtp_from_name" value="{{ old('smtp_from_name', $smtpFromName) }}" placeholder="Kai Properties">
            </div>
        </div>

        <div style="margin-bottom: 1rem; padding: 0.8rem; border: 1px dashed var(--border); border-radius: 10px;">
            <h4 style="margin: 0 0 0.6rem;">Validate SMTP Credentials</h4>
            <p class="muted" style="margin: 0 0 0.6rem;">Save settings first, then send a test email.</p>
            <form method="POST" action="{{ route('admin.settings.test-smtp') }}" style="display:flex; gap:0.6rem; flex-wrap: wrap; align-items: end;">
                @csrf
                <div style="min-width: 260px; flex:1;">
                    <label for="test_email">Test Recipient Email</label>
                    <input id="test_email" type="email" name="test_email" value="{{ old('test_email', auth()->user()?->email) }}" placeholder="you@example.com" required>
                </div>
                <button type="submit">Test SMTP</button>
            </form>
        </div>

        <hr style="margin: 1rem 0; border: 0; border-top: 1px solid var(--border);">

        <h3 style="margin-top: 0;">Arkesel SMS API Settings</h3>

        <div class="form-grid" style="margin-bottom: 0.8rem;">
            <div>
                <label for="arkesel_api_key">Arkesel API Key</label>
                <input id="arkesel_api_key" type="text" name="arkesel_api_key" value="{{ old('arkesel_api_key', $arkeselApiKey) }}" placeholder="Enter API key">
            </div>

            <div>
                <label for="arkesel_sender_id">Arkesel Sender ID</label>
                <input id="arkesel_sender_id" type="text" name="arkesel_sender_id" value="{{ old('arkesel_sender_id', $arkeselSenderId) }}" placeholder="KAI_PROP">
            </div>
        </div>

        <div style="margin-bottom: 1rem; padding: 0.8rem; border: 1px dashed var(--border); border-radius: 10px;">
            <h4 style="margin: 0 0 0.6rem;">Validate SMS Credentials</h4>
            <p class="muted" style="margin: 0 0 0.6rem;">Save settings first, then send a test SMS.</p>
            <form method="POST" action="{{ route('admin.settings.test-sms') }}" style="display:flex; gap:0.6rem; flex-wrap: wrap; align-items: end;">
                @csrf
                <div style="min-width: 220px; flex:1;">
                    <label for="test_phone">Test Recipient Phone</label>
                    <input id="test_phone" type="text" name="test_phone" value="{{ old('test_phone', auth()->user()?->phone) }}" placeholder="23320XXXXXXX" required>
                </div>
                <button type="submit">Test SMS</button>
            </form>
        </div>

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
