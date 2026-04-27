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
        if (!Schema::hasTable('type_of_coal')) {
            Schema::create('type_of_coal', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
                
                // Add index for name field for better search performance
                $table->index('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_of_coal');
    }
};
