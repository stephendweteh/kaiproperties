<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_own_profile_information(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Tenant User',
            'email' => 'tenant.profile@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TENANT,
            'phone' => '08000000001',
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Updated Tenant',
                'email' => 'tenant.updated@kai.local',
                'phone' => '08000000099',
                'password' => 'newpassword',
                'profile_photo' => UploadedFile::fake()->image('me.jpg', 200, 200),
            ])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Updated Tenant', $user->name);
        $this->assertSame('tenant.updated@kai.local', $user->email);
        $this->assertSame('08000000099', $user->phone);
        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }
}
