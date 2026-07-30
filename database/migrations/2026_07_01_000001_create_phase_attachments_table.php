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
        Schema::create('phase_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_phase_id')->constrained('ticket_phases')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->enum('attachment_type', ['image', 'document']);
            $table->timestamps();

            $table->index(['ticket_phase_id', 'attachment_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phase_attachments');
    }
};
