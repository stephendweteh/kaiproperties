<?php

namespace Tests\Feature;

use App\Models\MaintenanceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_properties_sync_between_web_admin_and_reference_api(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.sync@kai.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.properties.store'), [
                'name' => 'Sync Property',
                'code' => 'SYNC-001',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'address' => '10 Example Street',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.properties.index'));

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/references/properties')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Sync Property']);

        $propertyId = (int) \App\Models\Property::query()->where('code', 'SYNC-001')->value('id');

        $this->patchJson("/api/v1/admin/properties/{$propertyId}", [
            'name' => 'Sync Property',
            'code' => 'SYNC-001',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'address' => '10 Example Street',
            'is_active' => false,
        ])->assertOk();

        $this->getJson('/api/v1/references/properties')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Sync Property']);
    }

    public function test_categories_sync_between_web_admin_and_reference_api(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.category@kai.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Sync Category',
                'description' => 'Testing sync behavior',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.index'));

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/references/categories')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Sync Category']);

        $categoryId = (int) MaintenanceCategory::query()->where('name', 'Sync Category')->value('id');

        $this->patchJson("/api/v1/admin/categories/{$categoryId}", [
            'name' => 'Sync Category',
            'description' => 'Testing sync behavior',
            'is_active' => false,
        ])->assertOk();

        $this->getJson('/api/v1/references/categories')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Sync Category']);
    }

    public function test_users_sync_between_web_admin_and_api_admin_listing(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.users@kai.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Synced User',
                'email' => 'synced.user@kai.local',
                'phone' => '08001234567',
                'role' => User::ROLE_TENANT,
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.users.index'));

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonFragment(['email' => 'synced.user@kai.local']);
    }
}
