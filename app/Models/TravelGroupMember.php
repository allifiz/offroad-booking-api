<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelGroupMember extends Model
{
    protected $fillable = ['travel_group_id', 'user_id', 'is_leader', 'joined_at'];

    protected function casts(): array
    {
        return ['is_leader' => 'boolean', 'joined_at' => 'datetime'];
    }

    public function travelGroup(): BelongsTo { return $this->belongsTo(TravelGroup::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
