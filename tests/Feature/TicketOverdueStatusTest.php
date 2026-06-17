<?php

namespace Tests\Feature;

use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TicketOverdueStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_marks_past_due_logged_ticket_as_overdue(): void
    {
        $reporter = User::create([
            'name' => 'Ops User',
            'email' => 'ops.overdue@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $property = Property::create([
            'name' => 'Kai Overdue',
            'code' => 'KAI-OVD',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Overdue Street',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'General',
            'description' => 'General issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Past due ticket',
            'description' => 'Should become overdue',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $reporter->id,
            'status' => 'logged',
            'priority' => 'medium',
            'etd' => now()->subMinute(),
            'requires_additional_cost' => false,
        ]);

        Artisan::call('tickets:mark-overdue');

        $ticket->refresh();

        $this->assertSame('overdue', $ticket->status);
    }

    public function test_command_does_not_mark_on_hold_ticket_as_overdue(): void
    {
        $reporter = User::create([
            'name' => 'Ops User',
            'email' => 'ops.onhold@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $property = Property::create([
            'name' => 'Kai Hold',
            'code' => 'KAI-HLD',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Hold Street',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Plumbing',
            'description' => 'Plumbing issues',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'title' => 'Held ticket',
            'description' => 'Should remain on hold',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $reporter->id,
            'status' => 'on_hold',
            'priority' => 'medium',
            'etd' => now()->subMinute(),
            'requires_additional_cost' => false,
        ]);

        Artisan::call('tickets:mark-overdue');

        $ticket->refresh();

        $this->assertSame('on_hold', $ticket->status);
    }
}
