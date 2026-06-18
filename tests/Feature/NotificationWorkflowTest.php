<?php

namespace Tests\Feature;

use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_admin_user_creation_and_deletion_trigger_notifications(): void
    {
        $notificationService = Mockery::mock(NotificationService::class);
        $this->app->instance(NotificationService::class, $notificationService);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.notify@kai.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $notificationService->shouldReceive('sendUserCreated')
            ->once()
            ->with(Mockery::on(fn (User $user): bool => $user->email === 'new.user@kai.local'));

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New User',
                'email' => 'new.user@kai.local',
                'phone' => '233201234111',
                'role' => User::ROLE_TENANT,
                'password' => 'password123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $managedUser = User::create([
            'name' => 'Managed User',
            'email' => 'managed.user@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TENANT,
        ]);

        $notificationService->shouldReceive('sendUserDeleted')
            ->once()
            ->with(Mockery::on(fn (User $user): bool => $user->email === 'managed.user@kai.local'));

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $managedUser))
            ->assertRedirect(route('admin.users.index'));
    }

    public function test_tenant_ticket_logging_triggers_notifications(): void
    {
        $notificationService = Mockery::mock(NotificationService::class);
        $this->app->instance(NotificationService::class, $notificationService);

        $tenant = User::create([
            'name' => 'Tenant User',
            'email' => 'tenant.notify@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TENANT,
        ]);

        $property = Property::create([
            'name' => 'Kai Fault Block',
            'code' => 'KAI-FTB',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Fault Road',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Electrical',
            'description' => 'Electrical issues',
            'is_active' => true,
        ]);

        $notificationService->shouldReceive('sendTicketLogged')
            ->once()
            ->with(Mockery::type(Ticket::class));

        $this->actingAs($tenant)
            ->post(route('tickets.store'), [
                'title' => 'Fault report',
                'description' => 'Power is off in the unit',
                'property_id' => $property->id,
                'maintenance_category_id' => $category->id,
                'priority' => 'high',
            ])
            ->assertRedirect();
    }

    public function test_approver_review_triggers_notifications_for_approve_and_hold(): void
    {
        $notificationService = Mockery::mock(NotificationService::class);
        $this->app->instance(NotificationService::class, $notificationService);

        $approver = User::create([
            'name' => 'Approver User',
            'email' => 'approver.notify@kai.local',
            'password' => 'password',
            'role' => User::ROLE_APPROVER,
        ]);

        $technician = User::create([
            'name' => 'Tech User',
            'email' => 'tech.notify@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $property = Property::create([
            'name' => 'Kai Review',
            'code' => 'KAI-REV',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'address' => 'Review Street',
            'is_active' => true,
        ]);

        $category = MaintenanceCategory::create([
            'name' => 'Plumbing',
            'description' => 'Plumbing issues',
            'is_active' => true,
        ]);

        $approveTicket = Ticket::create([
            'title' => 'Approve me',
            'description' => 'Approve path',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $approver->id,
            'assigned_to' => $technician->id,
            'status' => 'pending_approval',
            'priority' => 'medium',
            'requires_additional_cost' => false,
        ]);

        $holdTicket = Ticket::create([
            'title' => 'Hold me',
            'description' => 'Hold path',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $approver->id,
            'assigned_to' => $technician->id,
            'status' => 'pending_approval',
            'priority' => 'medium',
            'requires_additional_cost' => false,
        ]);

        $notificationService->shouldReceive('sendTicketStatusChanged')
            ->twice()
            ->with(Mockery::type(Ticket::class), Mockery::type('string'));

        $this->actingAs($approver)
            ->post(route('tickets.review', $approveTicket), [
                'decision' => 'approve',
            ])
            ->assertRedirect(route('tickets.index'));

        $this->actingAs($approver)
            ->post(route('tickets.review', $holdTicket), [
                'decision' => 'hold',
            ])
            ->assertRedirect(route('tickets.index'));
    }

    public function test_admin_ticket_deletion_triggers_notification(): void
    {
        $notificationService = Mockery::mock(NotificationService::class);
        $this->app->instance(NotificationService::class, $notificationService);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.delete@kai.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
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
            'title' => 'Delete this ticket',
            'description' => 'Ticket delete notification test',
            'property_id' => $property->id,
            'maintenance_category_id' => $category->id,
            'reported_by' => $admin->id,
            'status' => 'logged',
            'priority' => 'low',
            'requires_additional_cost' => false,
        ]);

        $notificationService->shouldReceive('sendTicketDeleted')
            ->once()
            ->with(Mockery::type(Ticket::class));

        $this->actingAs($admin)
            ->delete(route('tickets.destroy', $ticket))
            ->assertRedirect(route('tickets.index'));
    }
}
