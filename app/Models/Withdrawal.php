<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    protected $fillable = ['driver_profile_id', 'points', 'amount', 'status', 'bank_name', 'account_number', 'account_name', 'requested_at', 'processed_at', 'processed_by', 'rejection_reason'];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'amount' => 'decimal:2',
            'status' => WithdrawalStatus::class,
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function driverProfile(): BelongsTo { return $this->belongsTo(DriverProfile::class); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}
