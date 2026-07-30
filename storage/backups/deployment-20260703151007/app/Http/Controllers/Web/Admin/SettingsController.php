<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit', [
            'siteName' => Setting::valueFor('site_name', 'Kai Properties'),
            'logoPath' => Setting::valueFor('logo_path'),
            'smtpHost' => Setting::valueFor('smtp_host'),
            'smtpPort' => Setting::valueFor('smtp_port', '587'),
            'smtpUsername' => Setting::valueFor('smtp_username'),
            'smtpPassword' => Setting::valueFor('smtp_password'),
            'smtpEncryption' => Setting::valueFor('smtp_encryption', 'tls'),
            'smtpFromEmail' => Setting::valueFor('smtp_from_email'),
            'smtpFromName' => Setting::valueFor('smtp_from_name', 'Kai Properties'),
            'arkeselApiKey' => Setting::valueFor('arkesel_api_key'),
            'arkeselSenderId' => Setting::valueFor('arkesel_sender_id'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'smtp_host' => ['nullable', 'string', 'max:120'],
            'smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'smtp_username' => ['nullable', 'string', 'max:190'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'in:tls,ssl,none'],
            'smtp_from_email' => ['nullable', 'email', 'max:190'],
            'smtp_from_name' => ['nullable', 'string', 'max:120'],
            'arkesel_api_key' => ['nullable', 'string', 'max:255'],
            'arkesel_sender_id' => ['nullable', 'string', 'max:20'],
        ]);

        Setting::setValue('site_name', $validated['site_name']);

        $currentLogoPath = Setting::valueFor('logo_path');

        if ($request->boolean('remove_logo') && $currentLogoPath) {
            Storage::disk('public')->delete($currentLogoPath);
            Setting::setValue('logo_path', null);
            $currentLogoPath = null;
        }

        if ($request->hasFile('logo')) {
            if ($currentLogoPath) {
                Storage::disk('public')->delete($currentLogoPath);
            }

            $newPath = $request->file('logo')->store('settings', 'public');
            Setting::setValue('logo_path', $newPath);
        }

        Setting::setValue('smtp_host', $validated['smtp_host'] ?? null);
        Setting::setValue('smtp_port', isset($validated['smtp_port']) ? (string) $validated['smtp_port'] : null);
        Setting::setValue('smtp_username', $validated['smtp_username'] ?? null);
        Setting::setValue('smtp_password', $validated['smtp_password'] ?? null);
        Setting::setValue('smtp_encryption', $validated['smtp_encryption'] ?? null);
        Setting::setValue('smtp_from_email', $validated['smtp_from_email'] ?? null);
        Setting::setValue('smtp_from_name', $validated['smtp_from_name'] ?? null);
        Setting::setValue('arkesel_api_key', $validated['arkesel_api_key'] ?? null);
        Setting::setValue('arkesel_sender_id', $validated['arkesel_sender_id'] ?? null);

        return redirect()->route('admin.settings.edit')->with('success', 'Settings updated successfully.');
    }

    public function resetData(Request $request)
    {
        $request->validate([
            'confirm_reset' => ['required', 'accepted'],
        ]);

        $attachmentPaths = TicketAttachment::query()
            ->pluck('file_path')
            ->filter()
            ->values();

        DB::transaction(function (): void {
            AuditLog::query()->delete();
            Ticket::query()->delete();
        });

        foreach ($attachmentPaths as $attachmentPath) {
            Storage::disk('public')->delete($attachmentPath);
        }

        foreach (File::glob(storage_path('logs/*.log')) ?: [] as $logFile) {
            File::delete($logFile);
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'Operational logs and entries have been reset. Categories were preserved.');
    }

    public function testSmtp(Request $request, NotificationService $notifications)
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:190'],
        ]);

        $sent = $notifications->sendEmail(
            $validated['test_email'],
            'Kai Properties SMTP Test',
            "SMTP test successful.\n\nSent at: ".now()->toDateTimeString()
        );

        if (! $sent) {
            return redirect()
                ->route('admin.settings.edit')
                ->with('error', 'SMTP test failed. Confirm SMTP settings, save, and try again.');
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'SMTP test email sent successfully.');
    }

    public function testSms(Request $request, NotificationService $notifications)
    {
        $validated = $request->validate([
            'test_phone' => ['required', 'string', 'max:30'],
        ]);

        $sent = $notifications->sendSms(
            $validated['test_phone'],
            'Kai Properties SMS test successful. Sent at: '.now()->format('Y-m-d H:i:s')
        );

        if (! $sent) {
            return redirect()
                ->route('admin.settings.edit')
                ->with('error', 'SMS test failed. Confirm Arkesel settings, save, and try again.');
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'SMS test message sent successfully.');
    }
}
