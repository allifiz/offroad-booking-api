<?php

namespace App\Models;

use App\Enums\DriverStatus;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'verification_status',
        'profile_photo_path',
        'license_number',
        'identity_number',
        'address',
        'date_of_birth',
        'joined_at',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'available_points',
        'held_points',
    ];

    protected function casts(): array
    {
        return [
            'status' => DriverStatus::class,
            'verification_status' => VerificationStatus::class,
            'date_of_birth' => 'date',
            'joined_at' => 'date',
            'verified_at' => 'datetime',
            'available_points' => 'integer',
            'held_points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DriverDocument::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
