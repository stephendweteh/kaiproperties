<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
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
}
