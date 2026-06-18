<?php

namespace Tests\Feature;

use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TechnicianRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_sees_only_assigned_jobs_in_web_index(): void
    {
        $technician = User::create([
            'name' => 'Tech One',
            'email' => 'tech.one@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $otherTechnician = User::create([
            'name' => 'Tech Two',
            'email' => 'tech.two@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $reporter = User::create([
            'name' => 'Ops User',
            'email' => 'ops.tech.index@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $property = Property::create([
            'name' => 'Kai Block',
            'code' => 'KAI-BLK',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Block Street',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Electrical',
            'description' => 'Electrical issues',
            'is_active' => true,
        ]);

        $assignedTicket = Ticket::create([
            'title' => 'Assigned ticket',
            'description' => 'Assigned to this technician',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $reporter->id,
            'assigned_to' => $technician->id,
            'status' => 'assigned',
            'priority' => 'medium',
            'requires_additional_cost' => false,
        ]);

        $otherTicket = Ticket::create([
            'title' => 'Other ticket',
            'description' => 'Assigned to another technician',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $reporter->id,
            'assigned_to' => $otherTechnician->id,
            'status' => 'assigned',
            'priority' => 'medium',
            'requires_additional_cost' => false,
        ]);

        $response = $this->actingAs($technician)->get(route('tickets.index'));

        $response->assertOk();
        $response->assertSeeText($assignedTicket->title);
        $response->assertDontSeeText($otherTicket->title);
    }

    public function test_technician_can_mark_assigned_job_completed_but_cannot_reassign_or_close(): void
    {
        $technician = User::create([
            'name' => 'Tech One',
            'email' => 'tech.update@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $otherTechnician = User::create([
            'name' => 'Tech Two',
            'email' => 'tech.update.other@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $reporter = User::create([
            'name' => 'Ops User',
            'email' => 'ops.tech.update@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $property = Property::create([
            'name' => 'Kai Yard',
            'code' => 'KAI-YRD',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Yard Road',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'General Maintenance',
            'description' => 'General issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Door hinge replacement',
            'description' => 'Replace broken hinge',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $reporter->id,
            'assigned_to' => $technician->id,
            'status' => 'assigned',
            'priority' => 'low',
            'requires_additional_cost' => false,
        ]);

        $this->actingAs($technician)
            ->put(route('tickets.update', $ticket), [
                'title' => $ticket->title,
                'description' => $ticket->description,
                'property_id' => $property->id,
                'maintenance_category_id' => $category->id,
                'unit' => null,
                'assigned_to' => $otherTechnician->id,
                'status' => 'closed',
                'priority' => 'low',
                'etd' => null,
            ])
            ->assertSessionHasErrors('status');

        $this->actingAs($technician)
            ->put(route('tickets.update', $ticket), [
                'title' => $ticket->title,
                'description' => $ticket->description,
                'property_id' => $property->id,
                'maintenance_category_id' => $category->id,
                'unit' => null,
                'assigned_to' => $otherTechnician->id,
                'status' => 'completed',
                'priority' => 'low',
                'etd' => null,
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket->refresh();

        $this->assertSame($technician->id, $ticket->assigned_to);
        $this->assertSame('completed', $ticket->status);
        $this->assertNotNull($ticket->completed_at);
    }

    public function test_technician_api_status_changes_limited_to_execution_statuses(): void
    {
        $technician = User::create([
            'name' => 'Tech One',
            'email' => 'tech.api.status@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $reporter = User::create([
            'name' => 'Ops User',
            'email' => 'ops.api.status@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $property = Property::create([
            'name' => 'Kai Metro',
            'code' => 'KAI-MTR',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Metro Street',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Carpentry',
            'description' => 'Woodwork issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Broken cabinet lock',
            'description' => 'Repair cabinet lock',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $reporter->id,
            'assigned_to' => $technician->id,
            'status' => 'assigned',
            'priority' => 'medium',
            'requires_additional_cost' => false,
        ]);

        Sanctum::actingAs($technician);

        $this->patchJson('/api/v1/tickets/'.$ticket->id.'/status', [
            'status' => 'closed',
        ])->assertForbidden();

        $this->patchJson('/api/v1/tickets/'.$ticket->id.'/status', [
            'status' => 'in_progress',
        ])->assertOk();

        $this->patchJson('/api/v1/tickets/'.$ticket->id.'/status', [
            'status' => 'completed',
        ])->assertOk();
    }

    public function test_assigned_technician_can_move_logged_ticket_to_in_progress_via_web(): void
    {
        $technician = User::create([
            'name' => 'Tech One',
            'email' => 'tech.logged.web@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $approver = User::create([
            'name' => 'Approver User',
            'email' => 'approver.logged.web@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $property = Property::create([
            'name' => 'Kai Flow',
            'code' => 'KAI-FLOW',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Flow Avenue',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Plumbing',
            'description' => 'Water issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Leaking valve',
            'description' => 'Main valve leaking',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $approver->id,
            'assigned_to' => $technician->id,
            'status' => 'logged',
            'priority' => 'medium',
            'requires_additional_cost' => false,
        ]);

        $this->actingAs($technician)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText($ticket->title)
            ->assertSeeText('Logged/New');

        $this->actingAs($technician)
            ->put(route('tickets.update', $ticket), [
                'status' => 'in_progress',
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket->refresh();

        $this->assertSame('in_progress', $ticket->status);
        $this->assertNotNull($ticket->started_at);
    }

    public function test_technician_update_status_button_disabled_on_hold_and_active_on_logged(): void
    {
        $technician = User::create([
            'name' => 'Tech One',
            'email' => 'tech.button.state@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $approver = User::create([
            'name' => 'Approver User',
            'email' => 'approver.button.state@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $property = Property::create([
            'name' => 'Kai Buttons',
            'code' => 'KAI-BTN',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Button Road',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'General',
            'description' => 'General issues',
            'is_active' => true,
        ]);

        $holdTicket = Ticket::create([
            'title' => 'Hold ticket',
            'description' => 'Should be disabled',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $approver->id,
            'assigned_to' => $technician->id,
            'status' => 'on_hold',
            'priority' => 'medium',
            'requires_additional_cost' => false,
        ]);

        $loggedTicket = Ticket::create([
            'title' => 'Logged ticket',
            'description' => 'Should be active',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $approver->id,
            'assigned_to' => $technician->id,
            'status' => 'logged',
            'priority' => 'medium',
            'requires_additional_cost' => false,
        ]);

        $response = $this->actingAs($technician)->get(route('tickets.index'));
        $response->assertOk();
        $response->assertSee('title="Status update disabled while ticket is on hold."', false);
        $response->assertSee(route('tickets.edit', $loggedTicket), false);

        $this->actingAs($technician)
            ->get(route('tickets.edit', $holdTicket))
            ->assertForbidden();
    }
}
