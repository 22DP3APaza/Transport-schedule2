<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;


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

}
