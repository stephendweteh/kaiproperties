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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no')->unique();
            $table->string('title');
            $table->text('description');
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_category_id')->constrained('maintenance_categories');
            $table->string('unit')->nullable();
            $table->foreignId('reported_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'logged',
                'assigned',
                'in_progress',
                'pending_approval',
                'on_hold',
                'completed',
                'closed',
                'rejected',
                'overdue',
            ])->default('logged');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->dateTime('etd')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->boolean('requires_additional_cost')->default(false);
            $table->timestamps();

            $table->index(['status', 'assigned_to']);
            $table->index(['property_id', 'maintenance_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
