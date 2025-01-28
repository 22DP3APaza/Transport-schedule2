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
    ];
}
