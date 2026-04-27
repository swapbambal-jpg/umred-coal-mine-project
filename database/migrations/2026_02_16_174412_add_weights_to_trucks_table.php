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
        Schema::table('trucks', function (Blueprint $table) {
            if (!Schema::hasColumn('trucks', 'netweight')) {
                $table->decimal('netweight', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('trucks', 'gross_weight')) {
                $table->decimal('gross_weight', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('trucks', 'tare_weight')) {
                $table->decimal('tare_weight', 10, 2)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->dropColumn(['netweight', 'gross_weight', 'tare_weight']);
        });
    }
};
