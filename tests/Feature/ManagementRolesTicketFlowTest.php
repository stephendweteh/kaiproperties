<?php

namespace Tests\Feature;

use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManagementRolesTicketFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_managing_director_logged_ticket_goes_to_pending_approval(): void
    {
        $director = User::create([
            'name' => 'Managing Director',
            'email' => 'md.flow@kai.local',
            'password' => 'password',
            'role' => User::ROLE_MANAGING_DIRECTOR,
            'is_approved' => true,
        ]);

        $property = Property::create([
            'name' => 'Kai Prime',
            'code' => 'KAI-PRI',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Prime Street',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Electrical',
            'description' => 'Electrical issues',
            'is_active' => true,
        ]);

        $this->actingAs($director)
            ->post(route('tickets.store'), [
                'title' => 'Generator check',
                'description' => 'Generator inspection request',
                'property_id' => $property->id,
                'maintenance_category_id' => $category->id,
                'priority' => 'high',
            ])
            ->assertRedirect();

        $ticket = Ticket::query()->firstOrFail();

        $this->assertSame($director->id, $ticket->reported_by);
        $this->assertSame('pending_approval', $ticket->status);
    }

    public function test_general_manager_can_see_all_tickets_in_api_index(): void
    {
        $generalManager = User::create([
            'name' => 'General Manager',
            'email' => 'gm.flow@kai.local',
            'password' => 'password',
            'role' => User::ROLE_GENERAL_MANAGER,
            'is_approved' => true,
        ]);

        $otherReporter = User::create([
            'name' => 'Other Reporter',
            'email' => 'other.reporter@kai.local',
            'password' => 'password',
            'role' => User::ROLE_MANAGING_DIRECTOR,
            'is_approved' => true,
        ]);

        $property = Property::create([
            'name' => 'Kai Edge',
            'code' => 'KAI-EDG',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Edge Lane',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Plumbing',
            'description' => 'Plumbing issues',
            'is_active' => true,
        ]);

        $ownTicket = Ticket::create([
            'title' => 'GM ticket',
            'description' => 'GM can view this',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $generalManager->id,
            'status' => 'pending_approval',
            'priority' => 'medium',
        ]);

        $otherTicket = Ticket::create([
            'title' => 'Other ticket',
            'description' => 'GM should also see this',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $otherReporter->id,
            'status' => 'pending_approval',
            'priority' => 'medium',
        ]);

        Sanctum::actingAs($generalManager);

        $response = $this->getJson('/api/v1/tickets');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $ownTicket->id]);
        $response->assertJsonFragment(['id' => $otherTicket->id]);
    }

    public function test_operations_manager_can_approve_and_assign_pending_ticket_via_api(): void
    {
        $operationsManager = User::create([
            'name' => 'Operations Manager',
            'email' => 'ops.flow@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
            'is_approved' => true,
        ]);

        $director = User::create([
            'name' => 'Managing Director',
            'email' => 'md.reporter@kai.local',
            'password' => 'password',
            'role' => User::ROLE_MANAGING_DIRECTOR,
            'is_approved' => true,
        ]);

        $technician = User::create([
            'name' => 'Tech User',
            'email' => 'tech.flow@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
            'is_approved' => true,
        ]);

        $property = Property::create([
            'name' => 'Kai Works',
            'code' => 'KAI-WRK',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Works Avenue',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Mechanical',
            'description' => 'Mechanical issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Pending assignment',
            'description' => 'Needs operations manager assignment',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $director->id,
            'status' => 'pending_approval',
            'priority' => 'high',
        ]);

        Sanctum::actingAs($operationsManager);

        $this->patchJson('/api/v1/tickets/'.$ticket->id.'/assign', [
            'assigned_to' => $technician->id,
        ])->assertOk();

        $ticket->refresh();

        $this->assertSame($technician->id, $ticket->assigned_to);
        $this->assertSame('assigned', $ticket->status);
    }

    public function test_operations_manager_cannot_hold_pending_ticket_without_assigned_technician(): void
    {
        $operationsManager = User::create([
            'name' => 'Operations Manager',
            'email' => 'ops.hold@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
            'is_approved' => true,
        ]);

        $director = User::create([
            'name' => 'Managing Director',
            'email' => 'md.hold@kai.local',
            'password' => 'password',
            'role' => User::ROLE_MANAGING_DIRECTOR,
            'is_approved' => true,
        ]);

        $property = Property::create([
            'name' => 'Kai Hold',
            'code' => 'KAI-HLD',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Hold Avenue',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Electrical',
            'description' => 'Electrical issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Unassigned pending ticket',
            'description' => 'Pending without technician',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $director->id,
            'status' => 'pending_approval',
            'priority' => 'high',
        ]);

        Sanctum::actingAs($operationsManager);

        $this->patchJson('/api/v1/tickets/'.$ticket->id.'/status', [
            'status' => 'on_hold',
        ])->assertStatus(422);
    }

    public function test_technician_cannot_log_ticket_in_web_or_api(): void
    {
        $technician = User::create([
            'name' => 'Tech No Create',
            'email' => 'tech.no.create@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
            'is_approved' => true,
        ]);

        $property = Property::create([
            'name' => 'Kai Restrict',
            'code' => 'KAI-RST',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Restrict Street',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'General',
            'description' => 'General issues',
            'is_active' => true,
        ]);

        $this->actingAs($technician)
            ->get(route('tickets.create'))
            ->assertForbidden();

        Sanctum::actingAs($technician);

        $this->postJson('/api/v1/tickets', [
            'title' => 'Technician should not create',
            'description' => 'Forbidden action',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'priority' => 'medium',
        ])->assertForbidden();
    }

    public function test_operations_manager_web_review_requires_assignment_for_hold(): void
    {
        $operationsManager = User::create([
            'name' => 'Operations Manager',
            'email' => 'ops.web.hold@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
            'is_approved' => true,
        ]);

        $director = User::create([
            'name' => 'Managing Director',
            'email' => 'md.web.hold@kai.local',
            'password' => 'password',
            'role' => User::ROLE_MANAGING_DIRECTOR,
            'is_approved' => true,
        ]);

        $property = Property::create([
            'name' => 'Kai Web Hold',
            'code' => 'KAI-WHB',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Web Hold Road',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Mechanical',
            'description' => 'Mechanical issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Web review hold',
            'description' => 'Should require assignment',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $director->id,
            'status' => 'pending_approval',
            'priority' => 'high',
        ]);

        $this->actingAs($operationsManager)
            ->post(route('tickets.review', $ticket), [
                'decision' => 'hold',
            ])
            ->assertSessionHasErrors('decision');

        $ticket->refresh();

        $this->assertSame('pending_approval', $ticket->status);
    }

    public function test_operations_manager_cannot_set_logged_ticket_on_hold_without_assignment_via_api(): void
    {
        $operationsManager = User::create([
            'name' => 'Operations Manager',
            'email' => 'ops.logged.hold@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
            'is_approved' => true,
        ]);

        $director = User::create([
            'name' => 'Managing Director',
            'email' => 'md.logged.hold@kai.local',
            'password' => 'password',
            'role' => User::ROLE_MANAGING_DIRECTOR,
            'is_approved' => true,
        ]);

        $property = Property::create([
            'name' => 'Kai Logged Hold',
            'code' => 'KAI-LGH',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Logged Hold Street',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'General',
            'description' => 'General issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Logged unassigned ticket',
            'description' => 'Should not be holdable when unassigned',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $director->id,
            'status' => 'logged',
            'priority' => 'medium',
            'assigned_to' => null,
        ]);

        Sanctum::actingAs($operationsManager);

        $this->patchJson('/api/v1/tickets/'.$ticket->id.'/status', [
            'status' => 'on_hold',
        ])->assertStatus(422);
    }

    public function test_general_manager_can_view_technician_work_progress_on_ticket_show_page(): void
    {
        $generalManager = User::create([
            'name' => 'General Manager',
            'email' => 'gm.progress@kai.local',
            'password' => 'password',
            'role' => User::ROLE_GENERAL_MANAGER,
            'is_approved' => true,
        ]);

        $reporter = User::create([
            'name' => 'Reporter User',
            'email' => 'reporter.progress@kai.local',
            'password' => 'password',
            'role' => User::ROLE_MANAGING_DIRECTOR,
            'is_approved' => true,
        ]);

        $technician = User::create([
            'name' => 'Tech Progress',
            'email' => 'tech.progress@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
            'is_approved' => true,
        ]);

        $property = Property::create([
            'name' => 'Kai Progress',
            'code' => 'KAI-PRG',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Progress Street',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'General',
            'description' => 'General issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Progress visibility ticket',
            'description' => 'Ensure management can view work progress on show page',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $reporter->id,
            'assigned_to' => $technician->id,
            'status' => 'in_progress',
            'priority' => 'medium',
            'started_at' => now(),
        ]);

        $ticket->phases()->create([
            'phase_name' => 'Phase 1',
            'phase_number' => 1,
            'status' => 'completed',
            'technician_notes' => 'Initial diagnostics done.',
            'manager_notes' => '[Operations Manager 2026-07-03 10:00] Continue to next step.',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->actingAs($generalManager)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText('Technician Work Progress')
            ->assertSeeText('Initial diagnostics done.');
    }
}
