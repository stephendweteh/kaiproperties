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
        Schema::table('ticket_phases', function (Blueprint $table): void {
            if (! Schema::hasColumn('ticket_phases', 'manager_notes')) {
                $table->text('manager_notes')->nullable()->after('technician_notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_phases', function (Blueprint $table): void {
            if (Schema::hasColumn('ticket_phases', 'manager_notes')) {
                $table->dropColumn('manager_notes');
            }
        });
    }
};