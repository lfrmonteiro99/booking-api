<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExternalProperty extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'description',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'rating',
        'raw_data'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'rating' => 'float',
        'raw_data' => 'array'
    ];

    public function property(): HasOne
    {
        return $this->hasOne(Property::class);
    }
} 