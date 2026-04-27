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
        if (!Schema::hasTable('mines')) {
            Schema::create('mines', function (Blueprint $table) {
                $table->id();
                $table->string('mine_name')->unique();
                $table->string('location')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('contact_phone')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
                
                // Indexes for better performance
                $table->index('mine_name');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mines');
    }
};
