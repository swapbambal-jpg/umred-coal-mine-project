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
       Schema::create('menus', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('route')->nullable(); // ex: users.index
        $table->string('icon')->nullable();  // ex: fas fa-users
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->integer('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
