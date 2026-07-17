<?php

namespace App\Models;

use App\Enums\PointLedgerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PointLedger extends Model
{
    protected $fillable = ['driver_profile_id', 'type', 'points', 'available_balance_after', 'held_balance_after', 'reference_type', 'reference_id', 'description', 'occurred_at'];

    protected function casts(): array
    {
        return [
            'type' => PointLedgerType::class,
            'points' => 'integer',
            'available_balance_after' => 'integer',
            'held_balance_after' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function driverProfile(): BelongsTo { return $this->belongsTo(DriverProfile::class); }
    public function reference(): MorphTo { return $this->morphTo(); }
}
