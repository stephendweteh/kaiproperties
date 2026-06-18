<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignupApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_signup_creates_pending_user(): void
    {
        $response = $this->post(route('signup'), [
            'name' => 'Pending User',
            'email' => 'pending.user@kai.local',
            'phone' => '233201234999',
            'role' => User::ROLE_TECHNICIAN,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'email' => 'pending.user@kai.local',
            'role' => User::ROLE_TECHNICIAN,
            'is_approved' => false,
        ]);
    }

    public function test_pending_user_cannot_login_until_operations_manager_approves(): void
    {
        $pendingUser = User::create([
            'name' => 'Pending User',
            'email' => 'pending.login@kai.local',
            'password' => 'password123',
            'role' => User::ROLE_TENANT,
            'is_approved' => false,
        ]);

        $this->post(route('login.attempt'), [
            'email' => 'pending.login@kai.local',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $operationsManager = User::create([
            'name' => 'Operations Manager',
            'email' => 'ops.approver@kai.local',
            'password' => 'password123',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $this->actingAs($operationsManager)
            ->post(route('admin.users.approve', $pendingUser))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'is_approved' => true,
            'approved_by' => $operationsManager->id,
        ]);

        $this->post(route('login.attempt'), [
            'email' => 'pending.login@kai.local',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_operations_manager_has_admin_access_except_settings(): void
    {
        $operationsManager = User::create([
            'name' => 'Operations Manager',
            'email' => 'ops.access@kai.local',
            'password' => 'password123',
            'role' => User::ROLE_OPERATIONS_MANAGER,
        ]);

        $this->actingAs($operationsManager)
            ->get(route('admin.properties.index'))
            ->assertOk();

        $this->actingAs($operationsManager)
            ->get(route('admin.users.index'))
            ->assertOk();

        $this->actingAs($operationsManager)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }
}
