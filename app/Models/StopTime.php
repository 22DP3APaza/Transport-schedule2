<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StopTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'arrival_time',
        'departure_time',
        'stop_id',
        'stop_sequence',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
    }

    public function stop()
    {
        return $this->belongsTo(Stop::class, 'stop_id', 'stop_id');
    }
}
