<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', Password::min(8)],
            'profile_photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_profile_photo' => ['nullable', 'boolean'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $newPhoto = $request->file('profile_photo');
        $removePhoto = $request->boolean('remove_profile_photo');

        unset($validated['profile_photo'], $validated['remove_profile_photo']);

        $currentPhotoPath = $user->profile_photo_path;

        if ($removePhoto && $currentPhotoPath) {
            Storage::disk('public')->delete($currentPhotoPath);
            $validated['profile_photo_path'] = null;
            $currentPhotoPath = null;
        }

        if ($newPhoto) {
            if ($currentPhotoPath) {
                Storage::disk('public')->delete($currentPhotoPath);
            }

            $validated['profile_photo_path'] = $newPhoto->store('users/profile-photos', 'public');
        }

        $user->update($validated);

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully.');
    }
}
