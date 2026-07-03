<?php

namespace Tests\Feature;

use App\Models\CostRequest;
use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OperationsManagerRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_manager_can_create_assign_and_close_ticket_via_web(): void
    {
        $operationsManager = User::create([
            'name' => 'Operations Manager',
            'email' => 'ops.web@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $technician = User::create([
            'name' => 'Tech User',
            'email' => 'tech.web@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $property = Property::create([
            'name' => 'Kai Court',
            'code' => 'KAI-CRT',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Court Street',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Electrical',
            'description' => 'Electrical issues',
            'is_active' => true,
        ]);

        $this->actingAs($operationsManager)
            ->post(route('tickets.store'), [
                'title' => 'Generator fault',
                'description' => 'Generator fails to start',
                'property_id' => $property->id,
                'maintenance_category_id' => $category->id,
                'reported_by' => $operationsManager->id,
                'priority' => 'high',
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket = Ticket::query()->firstOrFail();

        $this->assertSame('logged', $ticket->status);

        $this->actingAs($operationsManager)
            ->put(route('tickets.update', $ticket), [
                'title' => $ticket->title,
                'description' => $ticket->description,
                'property_id' => $property->id,
                'maintenance_category_id' => $category->id,
                'unit' => null,
                'assigned_to' => $technician->id,
                'status' => 'closed',
                'priority' => 'high',
                'etd' => now()->addDay()->toDateTimeString(),
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket->refresh();

        $this->assertSame($technician->id, $ticket->assigned_to);
        $this->assertSame('closed', $ticket->status);
    }

    public function test_operations_manager_can_approve_cost_request_via_api(): void
    {
        $operationsManager = User::create([
            'name' => 'Operations Manager',
            'email' => 'ops.api@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $technician = User::create([
            'name' => 'Tech User',
            'email' => 'tech.api@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $property = Property::create([
            'name' => 'Kai Plaza',
            'code' => 'KAI-PLZ',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Plaza Avenue',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'General Maintenance',
            'description' => 'General maintenance issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Lift motor replacement',
            'description' => 'Motor replacement required',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $operationsManager->id,
            'assigned_to' => $technician->id,
            'status' => 'pending_approval',
            'priority' => 'urgent',
            'requires_additional_cost' => true,
        ]);

        $costRequest = CostRequest::create([
            'ticket_id' => $ticket->id,
            'requested_by' => $technician->id,
            'amount' => 3200.00,
            'reason' => 'Motor and related parts',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($operationsManager);

        $this->patchJson('/api/v1/cost-requests/'.$costRequest->id.'/review', [
            'status' => 'approved',
            'reviewer_comment' => 'Approved by operations manager',
        ])->assertOk();

        $costRequest->refresh();
        $ticket->refresh();

        $this->assertSame('approved', $costRequest->status);
        $this->assertSame($operationsManager->id, $costRequest->reviewed_by);
        $this->assertSame('in_progress', $ticket->status);
    }

    public function test_operations_manager_can_delete_ticket_via_web(): void
    {
        $operationsManager = User::create([
            'name' => 'Operations Manager',
            'email' => 'ops.delete@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $property = Property::create([
            'name' => 'Kai Delete',
            'code' => 'KAI-DEL',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Delete Street',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'General',
            'description' => 'General issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Remove me',
            'description' => 'Ticket to be deleted',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $operationsManager->id,
            'status' => 'pending_approval',
            'priority' => 'medium',
        ]);

        $this->actingAs($operationsManager)
            ->delete(route('tickets.destroy', $ticket))
            ->assertRedirect(route('tickets.index'));

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_operations_manager_can_change_main_ticket_status_via_web_update_route(): void
    {
        $operationsManager = User::create([
            'name' => 'Operations Manager',
            'email' => 'ops.status@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $technician = User::create([
            'name' => 'Tech Status',
            'email' => 'tech.status@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $property = Property::create([
            'name' => 'Kai Status',
            'code' => 'KAI-STS',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Status Street',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Plumbing',
            'description' => 'Plumbing issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Status change request',
            'description' => 'Change status via update route',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $operationsManager->id,
            'assigned_to' => $technician->id,
            'status' => 'logged',
            'priority' => 'medium',
        ]);

        $this->actingAs($operationsManager)
            ->put(route('tickets.update', $ticket), [
                'title' => $ticket->title,
                'description' => $ticket->description,
                'property_id' => $property->id,
                'maintenance_category_id' => $category->id,
                'unit' => null,
                'assigned_to' => $technician->id,
                'status' => 'in_progress',
                'priority' => 'medium',
                'etd' => null,
                'estimated_cost' => null,
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket->refresh();

        $this->assertSame('in_progress', $ticket->status);
        $this->assertNotNull($ticket->started_at);
    }
}
