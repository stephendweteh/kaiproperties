<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class CostRequest extends Model
{
    protected $fillable = [
        'ticket_id',
        'requested_by',
        'amount',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_comment',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
