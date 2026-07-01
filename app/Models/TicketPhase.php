<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketPhase extends Model
{
    protected $fillable = [
        'ticket_id',
        'phase_name',
        'phase_number',
        'description',
        'technician_notes',
        'started_at',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PhaseAttachment::class);
    }

    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
