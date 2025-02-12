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
        Schema::create('trips', function (Blueprint $table) {
            $table->string('trip_id')->primary();
            $table->string('trip_headsign');
            $table->integer('direction_id');
            $table->integer('block_id');
            $table->string('wheelchair_accessible')->nullable();
            $table->string('route_id');
            $table->unsignedBigInteger('service_id');
            $table->string('shape_id');
            $table->timestamps();

            $table->foreign('route_id')->references('route_id')->on('routes')->onDelete('cascade');
            $table->foreign('service_id')->references('service_id')->on('calendar')->onDelete('cascade');
            $table->foreign('shape_id')->references('shape_id')->on('shapes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
