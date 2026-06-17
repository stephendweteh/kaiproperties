<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\AuditLog;
use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
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

    public function test_admin_can_reset_operational_data_and_keep_categories(): void
    {
        Storage::fake('public');

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.reset@kai.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $technician = User::create([
            'name' => 'Tech User',
            'email' => 'tech.reset@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Electrical',
            'description' => 'Electrical issues',
            'is_active' => true,
        ]);

        $property = Property::create([
            'name' => 'Kai Towers',
            'code' => 'KAI-TOW',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Airport Residential',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Power outage on floor 2',
            'description' => 'No lights in corridor',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $admin->id,
            'assigned_to' => $technician->id,
            'status' => 'logged',
            'priority' => 'high',
            'requires_additional_cost' => false,
        ]);

        $attachmentPath = 'tickets/attachments/evidence.png';

        Storage::disk('public')->put($attachmentPath, 'fake-image-content');

        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'uploaded_by' => $admin->id,
            'file_path' => $attachmentPath,
            'file_name' => 'evidence.png',
            'mime_type' => 'image/png',
            'file_size' => 100,
            'attachment_type' => 'image',
        ]);

        AuditLog::create([
            'auditable_type' => MaintenanceCategory::class,
            'auditable_id' => $category->id,
            'action' => AuditLog::ACTION_CREATED,
            'actor_id' => $admin->id,
            'meta' => ['name' => 'Electrical'],
        ]);

        File::ensureDirectoryExists(storage_path('logs'));
        File::put(storage_path('logs/laravel.log'), 'test log content');

        $this->actingAs($admin)
            ->post(route('admin.settings.reset-data'), [
                'confirm_reset' => '1',
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertDatabaseCount('maintenance_categories', 1);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('ticket_attachments', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        Storage::disk('public')->assertMissing($attachmentPath);
        $this->assertFalse(File::exists(storage_path('logs/laravel.log')));
    }
}
