<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calendar_date extends Model
{
    use HasFactory;

    protected $table = 'calendar';

    protected $fillable = [
       'date',
       'exception_type',
       'service_id',
    ];

    public function Calendar(): HasMany
    {
        return $this->hasMany(Shape::class, 'service_id', 'service_id');
    }
}
