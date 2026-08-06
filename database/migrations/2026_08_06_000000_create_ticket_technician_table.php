<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_technician', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['ticket_id', 'user_id']);
            $table->index('user_id');
        });

        $rows = DB::table('tickets')
            ->select('id', 'assigned_to')
            ->whereNotNull('assigned_to')
            ->get();

        $now = now();
        $insert = [];

        foreach ($rows as $row) {
            $insert[] = [
                'ticket_id' => $row->id,
                'user_id' => $row->assigned_to,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($insert !== []) {
            DB::table('ticket_technician')->insertOrIgnore($insert);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_technician');
    }
};