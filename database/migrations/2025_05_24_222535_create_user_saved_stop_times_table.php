<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_saved_stop_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('trip_id'); // Assuming trip_id is a string from GTFS
            $table->string('stop_id'); // Assuming stop_id is a string from GTFS
            $table->json('saved_times'); // To store the array of selected times
            $table->timestamps();

            // Add a unique constraint to prevent duplicate entries for the same user, trip, and stop
            $table->unique(['user_id', 'trip_id', 'stop_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_saved_stop_times');
    }
};
