<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseAttachment extends Model
{
    protected $fillable = [
        'ticket_phase_id',
        'uploaded_by',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'attachment_type',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(TicketPhase::class, 'ticket_phase_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getIsImageAttribute(): bool
    {
        return $this->attachment_type === 'image';
    }
}
