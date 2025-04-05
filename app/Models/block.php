<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    use HasFactory;


    protected $fillable = [
        'id',
        'sequence',
        'block_id',
        'service_id',
        'trip_id',
        'prev_trip_id',
        'next_trip_id',
        'start_stop_id',
        'end_stop_id',
    ];

}
