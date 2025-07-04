<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Availability extends Model
{
    use HasFactory;
    protected $fillable = [
        'room_id',
        'date',
        'is_available',
        'price',
        'max_guests'
    ];

    protected $casts = [
        'date' => 'date',
        'is_available' => 'boolean',
        'price' => 'decimal:2',
        'max_guests' => 'integer'
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
} 