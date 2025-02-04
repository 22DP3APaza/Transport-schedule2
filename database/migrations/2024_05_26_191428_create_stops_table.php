<?php

namespace App\Imports;

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
        Schema::create('stops', function (Blueprint $table) {
            $table->string('stop_id')->primary();
            $table->string('stop_name');
            $table->string('stop_desc')->nullable();
            $table->integer('stop_code');
            $table->double('stop_lat');
            $table->double('stop_lon');
            $table->string('stop_url');
            $table->string('location_type');
            $table->string('parent_station');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stops');
    }
};
