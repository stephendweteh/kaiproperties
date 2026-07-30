<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate any legacy approver users to operations manager before removing the enum value.
        DB::table('users')
            ->where('role', 'approver')
            ->update(['role' => 'operations_manager']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('tenant','admin','operations_manager','managing_director','general_manager','technician') NOT NULL DEFAULT 'tenant'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->whereIn('role', ['managing_director', 'general_manager'])
            ->update(['role' => 'operations_manager']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('tenant','admin','operations_manager','technician','approver') NOT NULL DEFAULT 'tenant'");
        }
    }
};
