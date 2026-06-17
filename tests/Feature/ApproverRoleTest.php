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

    public function test_approver_cannot_assign_or_manage_tickets_directly(): void
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
            'status' => 'logged',
            'priority' => 'medium',
            'requires_additional_cost' => false,
        ]);

        Sanctum::actingAs($approver);

        $this->patchJson('/api/v1/tickets/'.$ticket->id.'/assign', [
            'assigned_to' => $technician->id,
        ])->assertForbidden();

        $this->actingAs($approver)
            ->put(route('tickets.update', $ticket), [
                'title' => $ticket->title,
                'description' => $ticket->description,
                'property_id' => $property->id,
                'maintenance_category_id' => $category->id,
                'assigned_to' => $technician->id,
                'status' => 'closed',
                'priority' => 'medium',
            ])
            ->assertForbidden();
    }
}
