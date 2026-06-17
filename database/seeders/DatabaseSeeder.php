<?php

namespace Database\Seeders;

use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->create([
            'name' => 'System Admin',
            'email' => 'admin@kai.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'phone' => '08000000001',
        ]);

        User::query()->create([
            'name' => 'Operations Approver',
            'email' => 'approver@kai.local',
            'password' => 'password',
            'role' => User::ROLE_APPROVER,
            'phone' => '08000000002',
        ]);

        User::query()->create([
            'name' => 'Operations Manager',
            'email' => 'ops.manager@kai.local',
            'password' => 'password',
            'role' => User::ROLE_OPERATIONS_MANAGER,
            'phone' => '08000000006',
        ]);

        User::query()->create([
            'name' => 'Field Technician 1',
            'email' => 'tech1@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
            'phone' => '08000000003',
        ]);

        User::query()->create([
            'name' => 'Field Technician 2',
            'email' => 'tech2@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TECHNICIAN,
            'phone' => '08000000004',
        ]);

        User::query()->create([
            'name' => 'Tenant User',
            'email' => 'tenant@kai.local',
            'password' => 'password',
            'role' => User::ROLE_TENANT,
            'phone' => '08000000005',
        ]);

        Property::query()->insert([
            [
                'name' => 'Kai Towers',
                'code' => 'KT-001',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'address' => '12 Marina Road',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kai Gardens',
                'code' => 'KG-001',
                'city' => 'Abuja',
                'state' => 'FCT',
                'address' => '44 Central District',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        MaintenanceCategory::query()->insert([
            ['name' => 'Electrical', 'description' => 'Power and wiring related jobs', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Plumbing', 'description' => 'Water and drainage issues', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Carpentry', 'description' => 'Woodworks and fixtures', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Air Conditioning', 'description' => 'Cooling and HVAC maintenance', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Civil Works', 'description' => 'Structural and masonry jobs', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'General Maintenance', 'description' => 'Other maintenance requests', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
