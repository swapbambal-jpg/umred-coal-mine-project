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
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id')->nullable();
            $table->string('challan_number')->nullable();
            $table->decimal('gross_weight', 10, 2)->nullable();
            $table->decimal('tare_weight', 10, 2)->nullable();
            $table->decimal('netweight', 10, 2)->nullable();
            $table->decimal('sorted_exess', 10, 2)->nullable();
            $table->decimal('total_quantity_recieved', 10, 2)->nullable();
            $table->integer('total_trips')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plants');
    }
};
