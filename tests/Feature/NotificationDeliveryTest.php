<?php

namespace Tests\Feature;

use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_uses_saved_smtp_and_arkesel_settings(): void
    {
        Http::fake([
            'sms.arkesel.com/*' => Http::response(['status' => 'ok'], 200),
        ]);

        Setting::setValue('smtp_host', 'smtp.mailtrap.io');
        Setting::setValue('smtp_port', '2525');
        Setting::setValue('smtp_username', 'mail_user');
        Setting::setValue('smtp_password', 'mail_password');
        Setting::setValue('smtp_encryption', 'tls');
        Setting::setValue('smtp_from_email', 'notify@kai.local');
        Setting::setValue('smtp_from_name', 'Kai Notify');
        Setting::setValue('arkesel_api_key', 'ark_test_key');
        Setting::setValue('arkesel_sender_id', 'KAI_PROP');

        $operationsManager = User::create([
            'name' => 'Ops Manager',
            'email' => 'ops.notify@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $technician = User::create([
            'name' => 'Tech Notify',
            'email' => 'tech.notify@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
            'phone' => '233201234567',
        ]);

        $property = Property::create([
            'name' => 'Kai View',
            'code' => 'KAI-VW',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'View Road',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Electrical',
            'description' => 'Electrical issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Power outage',
            'description' => 'No power in lobby',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $operationsManager->id,
            'status' => 'logged',
            'priority' => 'high',
            'requires_additional_cost' => false,
        ]);

        Sanctum::actingAs($operationsManager);

        $this->patchJson('/api/v1/tickets/'.$ticket->id.'/assign', [
            'assigned_to' => $technician->id,
        ])->assertOk();

        $this->assertSame('smtp.mailtrap.io', config('mail.mailers.smtp.host'));
        $this->assertSame(2525, config('mail.mailers.smtp.port'));
        $this->assertSame('notify@kai.local', config('mail.from.address'));

        Http::assertSent(function (HttpRequest $request): bool {
            $body = $request->data();

            return $request->url() === 'https://sms.arkesel.com/api/v2/sms/send'
                && isset($body['sender'])
                && $body['sender'] === 'KAI_PROP'
                && isset($body['recipients'])
                && in_array('233201234567', $body['recipients'], true);
        });
    }
}
