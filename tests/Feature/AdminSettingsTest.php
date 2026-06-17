<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_site_name_and_upload_logo(): void
    {
        Storage::fake('public');

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.settings@kai.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'Kai Prime',
                'logo' => UploadedFile::fake()->image('logo.png', 120, 60),
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('Kai Prime', Setting::valueFor('site_name'));

        $logoPath = Setting::valueFor('logo_path');

        $this->assertNotNull($logoPath);
        Storage::disk('public')->assertExists($logoPath);
    }
}
