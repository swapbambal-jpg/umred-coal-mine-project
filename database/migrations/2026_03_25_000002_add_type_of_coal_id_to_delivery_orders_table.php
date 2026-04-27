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
        Schema::table('delivery_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_orders', 'type_of_coal_id')) {
                $table->unsignedBigInteger('type_of_coal_id')->nullable()->after('type_of_mode_id');
                
                // Add foreign key constraint
                $table->foreign('type_of_coal_id')
                      ->references('id')
                      ->on('type_of_coal')
                      ->onDelete('set null');
                      
                // Add index for better performance
                $table->index('type_of_coal_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_orders', 'type_of_coal_id')) {
                // Drop foreign key first
                $table->dropForeign(['type_of_coal_id']);
                // Drop the column
                $table->dropColumn('type_of_coal_id');
            }
        });
    }
};
