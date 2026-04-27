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
        // Foreign key constraint is already handled in the table creation migration
        // This migration is kept for compatibility but does nothing
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Foreign key constraint is handled in the table creation migration
        // This migration is kept for compatibility but does nothing
    }
};
