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
            if (!Schema::hasColumn('delivery_orders', 'cil_company_id')) {
                $table->string('cil_company_id')->nullable()->after('type_of_purchase_id');
                $table->foreign('cil_company_id')->references('cil_id')->on('cil_subsidiary_companies')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_orders', 'cil_company_id')) {
                $table->dropForeign(['cil_company_id']);
                $table->dropColumn('cil_company_id');
            }
        });
    }
};
