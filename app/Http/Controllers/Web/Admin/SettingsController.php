<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketAttachment;
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
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
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
}
