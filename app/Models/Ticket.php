<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    public const STATUSES = [
        'logged',
        'assigned',
        'in_progress',
        'pending_approval',
        'on_hold',
        'completed',
        'closed',
        'rejected',
        'overdue',
    ];

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public const ESTIMATED_COST_CURRENCIES = ['GBP', 'USD', 'EUR', 'GHS', 'CNY'];

    public const ESTIMATED_COST_CURRENCY_SYMBOLS = [
        'GBP' => 'GBP£',
        'USD' => 'USD$',
        'EUR' => 'EUR€',
        'GHS' => 'GHS₵',
        'CNY' => 'CNY¥',
    ];

    protected $fillable = [
        'ticket_no',
        'title',
        'description',
        'property_id',
        'maintenance_category_id',
        'unit',
        'reported_by',
        'assigned_to',
        'status',
        'priority',
        'etd',
        'estimated_cost',
        'estimated_cost_currency',
        'started_at',
        'completed_at',
        'closed_at',
        'requires_additional_cost',
        'current_phase',
    ];

    protected $casts = [
        'etd' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'requires_additional_cost' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if (! $ticket->ticket_no) {
                $ticket->ticket_no = 'KAI-'.strtoupper(Str::random(8));
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCategory::class, 'maintenance_category_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function costRequests(): HasMany
    {
        return $this->hasMany(CostRequest::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class)->latest();
    }

    public function phases(): HasMany
    {
        return $this->hasMany(TicketPhase::class)->orderBy('phase_number');
    }
}
