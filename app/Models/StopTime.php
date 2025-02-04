<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class StopTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'arrival_time',
        'departure_time',
        'stop_id',
        'stop_sequence',
        'pickup_type',
        'drop_off_type',

    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'stop_id', 'stop_id');
    }
}
