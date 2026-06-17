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

class ApproverRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_approver_can_review_cost_requests_and_place_job_on_hold(): void
    {
        $approver = User::create([
            'name' => 'Approver User',
            'email' => 'approver.role@kai.local',
            'password' => 'password',
            'role' => User::ROLE_APPROVER,
        ]);

        $technician = User::create([
            'name' => 'Tech User',
            'email' => 'tech.approver@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $property = Property::create([
            'name' => 'Kai Annex',
            'code' => 'KAI-ANX',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Annex Road',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Plumbing',
            'description' => 'Plumbing issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Pipe replacement',
            'description' => 'Burst pipe in service area',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $approver->id,
            'assigned_to' => $technician->id,
            'status' => 'pending_approval',
            'priority' => 'high',
            'requires_additional_cost' => true,
        ]);

        $costRequest = CostRequest::create([
            'ticket_id' => $ticket->id,
            'requested_by' => $technician->id,
            'amount' => 450.00,
            'reason' => 'Pipe and fittings',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($approver);

        $this->patchJson('/api/v1/cost-requests/'.$costRequest->id.'/review', [
            'status' => 'rejected',
            'reviewer_comment' => 'Rejected pending budget approval',
        ])->assertOk();

        $costRequest->refresh();
        $ticket->refresh();

        $this->assertSame('rejected', $costRequest->status);
        $this->assertSame($approver->id, $costRequest->reviewed_by);
        $this->assertSame('on_hold', $ticket->status);
    }

    public function test_approver_can_review_pending_ticket_via_web_and_place_on_hold(): void
    {
        $approver = User::create([
            'name' => 'Approver User',
            'email' => 'approver.restrict@kai.local',
            'password' => 'password',
            'role' => User::ROLE_APPROVER,
        ]);

        $technician = User::create([
            'name' => 'Tech User',
            'email' => 'tech.restrict@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $property = Property::create([
            'name' => 'Kai Hub',
            'code' => 'KAI-HUB',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Hub Street',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Electrical',
            'description' => 'Electrical issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Faulty switchboard',
            'description' => 'Switchboard keeps tripping',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $approver->id,
            'assigned_to' => $technician->id,
            'status' => 'pending_approval',
            'priority' => 'medium',
            'requires_additional_cost' => false,
        ]);

        $response = $this->actingAs($approver)->get(route('tickets.index'));
        $response->assertOk();
        $response->assertSeeText($ticket->title);

        $this->actingAs($approver)
            ->put(route('tickets.update', $ticket), [
                'assigned_to' => $technician->id,
                'status' => 'on_hold',
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket->refresh();

        $this->assertSame('on_hold', $ticket->status);

        Sanctum::actingAs($approver);

        $this->patchJson('/api/v1/tickets/'.$ticket->id.'/assign', [
            'assigned_to' => $technician->id,
        ])->assertOk();
    }

    public function test_approver_can_approve_pending_ticket_to_logged_status_via_web(): void
    {
        $approver = User::create([
            'name' => 'Approver User',
            'email' => 'approver.approve.logged@kai.local',
            'password' => 'password',
            'role' => User::ROLE_APPROVER,
        ]);

        $technician = User::create([
            'name' => 'Tech User',
            'email' => 'tech.approve.logged@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $property = Property::create([
            'name' => 'Kai Core',
            'code' => 'KAI-CORE',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Core Road',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Mechanical',
            'description' => 'Mechanical issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Fan motor noise',
            'description' => 'Fan motor has unusual noise',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $approver->id,
            'assigned_to' => $technician->id,
            'status' => 'pending_approval',
            'priority' => 'medium',
        ]);

        $this->actingAs($approver)
            ->put(route('tickets.update', $ticket), [
                'assigned_to' => $technician->id,
                'status' => 'logged',
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket->refresh();

        $this->assertSame('logged', $ticket->status);
        $this->assertSame($technician->id, $ticket->assigned_to);
    }

    public function test_view_and_review_page_shows_back_approve_hold_and_approves_instantly(): void
    {
        $approver = User::create([
            'name' => 'Approver User',
            'email' => 'approver.instant@kai.local',
            'password' => 'password',
            'role' => User::ROLE_APPROVER,
        ]);

        $technician = User::create([
            'name' => 'Tech User',
            'email' => 'tech.instant@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $property = Property::create([
            'name' => 'Kai Instant',
            'code' => 'KAI-INST',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Instant Lane',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Electrical',
            'description' => 'Electrical issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Review now',
            'description' => 'Needs instant review',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $approver->id,
            'assigned_to' => $technician->id,
            'status' => 'pending_approval',
            'priority' => 'medium',
        ]);

        $this->actingAs($approver)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText('Back to Tickets')
            ->assertSeeText('Approve')
            ->assertSeeText('Hold');

        $this->actingAs($approver)
            ->post(route('tickets.review', $ticket), [
                'decision' => 'approve',
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket->refresh();

        $this->assertSame('logged', $ticket->status);

        $this->actingAs($approver)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText($ticket->title)
            ->assertSeeText('Logged/New');
    }

    public function test_approver_cannot_access_log_ticket_page(): void
    {
        $approver = User::create([
            'name' => 'Approver User',
            'email' => 'approver.no.create@kai.local',
            'password' => 'password',
            'role' => User::ROLE_APPROVER,
        ]);

        $this->actingAs($approver)
            ->get(route('tickets.create'))
            ->assertForbidden();
    }

    public function test_on_hold_ticket_remains_visible_in_approver_list(): void
    {
        $approver = User::create([
            'name' => 'Approver User',
            'email' => 'approver.hold.visible@kai.local',
            'password' => 'password',
            'role' => User::ROLE_APPROVER,
        ]);

        $technician = User::create([
            'name' => 'Tech User',
            'email' => 'tech.hold.visible@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $property = Property::create([
            'name' => 'Kai Visible',
            'code' => 'KAI-VIS',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Visible Road',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Mechanical',
            'description' => 'Mechanical issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Should stay visible',
            'description' => 'Keep in list after hold',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $approver->id,
            'assigned_to' => $technician->id,
            'status' => 'pending_approval',
            'priority' => 'medium',
        ]);

        $this->actingAs($approver)
            ->post(route('tickets.review', $ticket), [
                'decision' => 'hold',
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket->refresh();

        $this->assertSame('on_hold', $ticket->status);

        $this->actingAs($approver)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText($ticket->title)
            ->assertSeeText('On Hold');
    }

    public function test_approver_can_review_ticket_via_ajax_without_navigation(): void
    {
        $approver = User::create([
            'name' => 'Approver User',
            'email' => 'approver.ajax@kai.local',
            'password' => 'password',
            'role' => User::ROLE_APPROVER,
        ]);

        $technician = User::create([
            'name' => 'Tech User',
            'email' => 'tech.ajax@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $property = Property::create([
            'name' => 'Kai Ajax',
            'code' => 'KAI-AJX',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Ajax Road',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'General',
            'description' => 'General issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Inline review ticket',
            'description' => 'Ticket for ajax review test',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $approver->id,
            'assigned_to' => $technician->id,
            'status' => 'pending_approval',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($approver)
            ->postJson(route('tickets.review', $ticket), [
                'decision' => 'approve',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.ticket_id', $ticket->id)
            ->assertJsonPath('data.status', 'logged')
            ->assertJsonPath('data.status_label', 'Logged/New');

        $ticket->refresh();

        $this->assertSame('logged', $ticket->status);
    }
}
