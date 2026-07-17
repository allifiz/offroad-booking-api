<?php

namespace App\Models;

use App\Enums\TravelGroupSource;
use App\Enums\TravelGroupStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TravelGroup extends Model
{
    protected $fillable = ['name', 'source', 'leader_user_id', 'created_by', 'status', 'member_limit', 'notes'];

    protected function casts(): array
    {
        return [
            'source' => TravelGroupSource::class,
            'status' => TravelGroupStatus::class,
            'member_limit' => 'integer',
        ];
    }

    public function leader(): BelongsTo { return $this->belongsTo(User::class, 'leader_user_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function members(): HasMany { return $this->hasMany(TravelGroupMember::class); }
    public function bookings(): HasMany { return $this->hasMany(Booking::class); }
}
