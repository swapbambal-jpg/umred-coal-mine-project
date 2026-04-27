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
        if (!Schema::hasTable('role_menu_permissions')) {
            Schema::create('role_menu_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('menu_id');

                $table->boolean('can_view')->default(false);
                $table->boolean('can_add')->default(false);
                $table->boolean('can_edit')->default(false);
                $table->boolean('can_delete')->default(false);

                $table->timestamps();

                $table->unique(['role_id', 'menu_id']);

                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_menu_permissions');
    }
};
