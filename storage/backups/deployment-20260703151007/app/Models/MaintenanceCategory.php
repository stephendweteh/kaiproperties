<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class MaintenanceCategory extends Model
{
    use Auditable;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
