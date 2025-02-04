<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Shape extends Model
{
    use HasFactory;

    protected $fillable = [
        'shape_id',
        'shape_pt_lon',
        'shape_pt_lat',
        'shape_pt_sequence',
        'shape_dist_traveled',
    ];
}
