<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stop extends Model
{
    use HasFactory;

    protected $primaryKey = 'stop_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'stop_id',
        'stop_name',
        'stop_desc',
        'stop_code',
        'stop_lat',
        'stop_lon',
        'stop_url',
        'location_type',
        'parent_station',
        'wheelchair_boarding',
        

    ];
}
