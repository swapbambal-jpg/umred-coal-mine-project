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
        if (!Schema::hasTable('trucks')) {
            Schema::create('trucks', function (Blueprint $table) {
                $table->id();
                $table->string('truck_name');
                $table->string('mode_name');
                $table->date('registration_date');
                $table->string('chassis_no')->unique();
                $table->string('engine_no')->unique();
                $table->string('insurance_company');
                $table->string('insurance_policy_no');
                $table->date('insurance_valid_up_to');
                $table->string('pucc_no');
                $table->date('pucc_valid_up_to');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
