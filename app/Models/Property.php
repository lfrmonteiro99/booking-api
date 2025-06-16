<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        'property_id',
        'name',
        'description',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'rating'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'rating' => 'float'
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
} 