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
    Schema::create('shapes', function (Blueprint $table) {
        $table->string('shape_id')->primary();
        $table->double('shape_pt_lat');
        $table->double('shape_pt_lon');
        $table->integer('shape_pt_sequence');
        $table->double('shape_dist_traveled')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
