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
            if (!Schema::hasColumn('delivery_orders', 'grade_id')) {
                $table->unsignedBigInteger('grade_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('delivery_orders', 'type_of_purchase_id')) {
                $table->unsignedBigInteger('type_of_purchase_id')->nullable()->after('grade_id');
            }
            if (!Schema::hasColumn('delivery_orders', 'delivery_challan_number')) {
                $table->string('delivery_challan_number', 255)->nullable()->after('type_of_purchase_id');
            }
            
            // Add foreign key constraints if you have the related tables
            // $table->foreign('grade_id')->references('id')->on('grades')->onDelete('set null');
            // $table->foreign('type_of_purchase_id')->references('id')->on('type_of_purchases')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropColumn(['grade_id', 'type_of_purchase_id', 'delivery_challan_number']);
        });
    }
};
