<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Reedware\LaravelCompositeRelations\HasCompositeRelations;
class CalendarDate extends Model
{
    use HasCompositeRelations;

    protected $primaryKey = ['service_id', 'date'];
    public $incrementing = false;
    protected $keyType = 'array';


    protected $fillable = [
       'date',
       'exception_type',
       'service_id',
    ];


}
