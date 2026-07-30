<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_with_profile_photo(): void
    {
        Storage::fake('public');

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.photo@kai.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Photo User',
                'email' => 'photo.user@kai.local',
                'phone' => '08000000099',
                'role' => User::ROLE_TENANT,
                'password' => 'password',
                'profile_photo' => UploadedFile::fake()->image('profile.jpg', 240, 240),
            ])
            ->assertRedirect(route('admin.users.index'));

        $createdUser = User::query()->where('email', 'photo.user@kai.local')->first();

        $this->assertNotNull($createdUser);
        $this->assertNotNull($createdUser->profile_photo_path);
        Storage::disk('public')->assertExists($createdUser->profile_photo_path);
    }

    public function test_admin_can_remove_existing_user_profile_photo(): void
    {
        Storage::fake('public');

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.remove.photo@kai.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $path = UploadedFile::fake()->image('existing.jpg', 240, 240)->store('users/profile-photos', 'public');

        $managedUser = User::create([
            'name' => 'Managed User',
            'email' => 'managed.user@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TENANT,
            'profile_photo_path' => $path,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $managedUser), [
                'name' => 'Managed User',
                'email' => 'managed.user@kai.local',
                'phone' => '',
                'role' => User::ROLE_TENANT,
                'remove_profile_photo' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $managedUser->refresh();

        $this->assertNull($managedUser->profile_photo_path);
        Storage::disk('public')->assertMissing($path);
    }
}
