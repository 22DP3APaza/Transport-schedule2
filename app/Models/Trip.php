<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'direction_id',
        'block_id',
        'wheelchair_accessible',
        'shape_id',
        'trip_type',
        'trip_short_name',
    ];

    public function Route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id', 'route_id');
    }

    public function StopTime(): HasMany
    {
        return $this->hasMany(StopTime::class, 'trip_id', 'trip_id');
    }


    public function Shape(): HasMany
    {
        return $this->hasMany(Shape::class, 'shape_id', 'shape_id');
    }

}
