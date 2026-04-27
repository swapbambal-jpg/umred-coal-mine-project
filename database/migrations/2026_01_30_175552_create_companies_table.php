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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 255);
            $table->string('gst_number', 50)->unique();
            $table->text('address');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index('company_name');
            $table->index('gst_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
