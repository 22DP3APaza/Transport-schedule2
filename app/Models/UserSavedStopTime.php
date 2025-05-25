<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Add this line if you want to define the user relationship

class UserSavedStopTime extends Model
{
    use HasFactory;

    protected $table = 'user_saved_stop_times';

    // Define fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'trip_id',
        'stop_id',
        'saved_times',
    ];

    // Cast 'saved_times' to an array so Laravel handles JSON encoding/decoding
    protected $casts = [
        'saved_times' => 'array',
    ];

    /**
     * Get the user that owns the saved stop time.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
