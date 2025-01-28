<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $primaryKey = 'trip_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'trip_id',
        'route_id',
        'service_id',
        'trip_headsign',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'route_id');
    }

    public function stopTimes()
    {
        return $this->hasMany(StopTime::class, 'trip_id', 'trip_id');
    }
}
