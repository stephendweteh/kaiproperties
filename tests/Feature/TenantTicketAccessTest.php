<?php

namespace Tests\Feature;

use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTicketAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_only_see_own_tickets_in_index(): void
    {
        $tenant = User::create([
            'name' => 'Tenant User',
            'email' => 'tenant.access@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TENANT,
        ]);

        $otherTenant = User::create([
            'name' => 'Another Tenant',
            'email' => 'tenant.other@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TENANT,
        ]);

        $property = Property::create([
            'name' => 'Kai Gardens',
            'code' => 'KAI-GDN',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Main Road',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Plumbing',
            'description' => 'Water issues',
            'is_active' => true,
        ]);

        $ownTicket = Ticket::create([
            'title' => 'Tenant own ticket',
            'description' => 'Own issue',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $tenant->id,
            'status' => 'logged',
            'priority' => 'medium',
            'requires_additional_cost' => false,
        ]);

        $otherTicket = Ticket::create([
            'title' => 'Other tenant ticket',
            'description' => 'Other issue',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $otherTenant->id,
            'status' => 'logged',
            'priority' => 'medium',
            'requires_additional_cost' => false,
        ]);

        $response = $this->actingAs($tenant)->get(route('tickets.index'));

        $response->assertOk();
        $response->assertSeeText($ownTicket->title);
        $response->assertDontSeeText($otherTicket->title);
    }

    public function test_tenant_can_create_ticket_but_cannot_assign_or_spoof_reporter(): void
    {
        $tenant = User::create([
            'name' => 'Tenant User',
            'email' => 'tenant.create@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TENANT,
        ]);

        $technician = User::create([
            'name' => 'Tech User',
            'email' => 'tech.create@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other.user@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TENANT,
        ]);

        $property = Property::create([
            'name' => 'Kai Towers',
            'code' => 'KAI-TWR',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Airport Road',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Electrical',
            'description' => 'Power issues',
            'is_active' => true,
        ]);

        $this->actingAs($tenant)
            ->post(route('tickets.store'), [
                'title' => 'No power in kitchen',
                'description' => 'Lights are out',
                'property_id' => $property->id,
                'maintenance_category_id' => $category->id,
                'reported_by' => $otherUser->id,
                'assigned_to' => $technician->id,
                'priority' => 'high',
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket = Ticket::query()->latest('id')->firstOrFail();

        $this->assertSame($tenant->id, $ticket->reported_by);
        $this->assertNull($ticket->assigned_to);
        $this->assertSame('logged', $ticket->status);
    }

    public function test_tenant_cannot_access_ticket_edit_or_update_routes(): void
    {
        $tenant = User::create([
            'name' => 'Tenant User',
            'email' => 'tenant.edit@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TENANT,
        ]);

        $property = Property::create([
            'name' => 'Kai Estate',
            'code' => 'KAI-EST',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Estate Drive',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'General Maintenance',
            'description' => 'General maintenance issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Broken handle',
            'description' => 'Door handle is broken',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $tenant->id,
            'status' => 'logged',
            'priority' => 'medium',
            'requires_additional_cost' => false,
        ]);

        $this->actingAs($tenant)
            ->get(route('tickets.edit', $ticket))
            ->assertForbidden();

        $this->actingAs($tenant)
            ->put(route('tickets.update', $ticket), [
                'title' => 'Updated title',
                'description' => 'Updated description',
                'property_id' => $property->id,
                'maintenance_category_id' => $category->id,
                'assigned_to' => null,
                'status' => 'completed',
                'priority' => 'low',
            ])
            ->assertForbidden();
    }
}
