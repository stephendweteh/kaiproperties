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
        Schema::create('ticket_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('phase_name'); // e.g., "Phase 1", "Phase 2", etc.
            $table->integer('phase_number');
            $table->text('description')->nullable();
            $table->text('technician_notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->timestamps();

            $table->index(['ticket_id', 'phase_number']);
        });

        // Add phases column to tickets table if needed
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'current_phase')) {
                $table->integer('current_phase')->default(0)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'current_phase')) {
                $table->dropColumn('current_phase');
            }
        });
        Schema::dropIfExists('ticket_phases');
    }
};
