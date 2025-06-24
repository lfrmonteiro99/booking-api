<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_id',
        'name',
        'description',
        'max_guests'
    ];

    protected $casts = [
        'max_guests' => 'integer'
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }
} 