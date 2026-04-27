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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'adhar_number')) {
                $table->string('adhar_number', 12)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'adhar_photo')) {
                $table->string('adhar_photo')->nullable()->after('adhar_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['adhar_number', 'adhar_photo']);
        });
    }
};
