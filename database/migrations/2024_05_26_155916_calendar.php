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
        Schema::create('calendar', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id')->primary();
            $table->tinyInteger('monday')->default(0);
            $table->tinyInteger('tuesday')->default(0);
            $table->tinyInteger('wednesday')->default(0);
            $table->tinyInteger('thursday')->default(0);
            $table->tinyInteger('friday')->default(0);
            $table->tinyInteger('saturday')->default(0);
            $table->tinyInteger('sunday')->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->index('start_date');
            $table->index('end_date');
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
