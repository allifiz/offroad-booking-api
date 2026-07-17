<?php

namespace App\Models;

use App\Enums\DriverStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverProfile extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'license_number',
        'identity_number',
        'address',
        'date_of_birth',
        'joined_at',
        'available_points',
        'held_points',
    ];

    protected function casts(): array
    {
        return [
            'status' => DriverStatus::class,
            'date_of_birth' => 'date',
            'joined_at' => 'date',
            'available_points' => 'integer',
            'held_points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
