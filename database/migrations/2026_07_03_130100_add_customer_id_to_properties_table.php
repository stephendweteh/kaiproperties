<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('properties') || ! Schema::hasTable('customers')) {
            return;
        }

        if (Schema::hasColumn('properties', 'customer_id')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table): void {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('address')
                ->constrained('customers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('properties') || ! Schema::hasColumn('properties', 'customer_id')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
