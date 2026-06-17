<?php

namespace Tests\Feature;

use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_manager_can_upload_images_camera_photo_and_documents_on_ticket_create(): void
    {
        Storage::fake('public');

        $admin = User::create([
            'name' => 'Operations Manager',
            'email' => 'ops@kai.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $property = Property::create([
            'name' => 'Kai Plaza',
            'code' => 'KP-01',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Electrical',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('tickets.store'), [
            'title' => 'Fault with hallway light',
            'description' => 'Bulb sparks intermittently in hallway',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $admin->id,
            'priority' => 'high',
            'image_attachments' => [UploadedFile::fake()->image('fault.jpg')],
            'camera_attachment' => UploadedFile::fake()->image('capture.jpg'),
            'document_attachments' => [UploadedFile::fake()->create('note.pdf', 128, 'application/pdf')],
        ]);

        $response->assertRedirect(route('tickets.index'));

        $ticket = Ticket::query()->first();

        $this->assertNotNull($ticket);
        $this->assertCount(3, $ticket->attachments()->get());

        foreach ($ticket->attachments as $attachment) {
            Storage::disk('public')->assertExists($attachment->file_path);
        }
    }
}
