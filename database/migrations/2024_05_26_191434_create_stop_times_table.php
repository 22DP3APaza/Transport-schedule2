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
        Schema::create('stop_times', function (Blueprint $table) {
            $table->time('arrival_time')->primary();
            $table->integer('stop_sequence');
            $table->time('departure_time');
            $table->integer('pickup_type');
            $table->integer('drop_off_type');
            $table->string('trip_id');
            $table->string('stop_id');
            $table->timestamps();

            $table->foreign('trip_id')->references('trip_id')->on('trips')->onDelete('cascade');
            $table->foreign('stop_id')->references('stop_id')->on('stops')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stop_times');
    }
};
