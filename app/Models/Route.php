<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

    protected $primaryKey = 'route_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'route_id',
        'route_short_name',
        'route_long_name',
        'route_desc',
        'route_type',
        'route_url',
        'route_color',
        'route_text_color',
        'route_sort_order',
        'min_headway_minutes',

    ];
}
