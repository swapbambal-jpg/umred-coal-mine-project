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
        if (!Schema::hasTable('delivery_orders')) {
            Schema::create('delivery_orders', function (Blueprint $table) {
                $table->id();
                $table->string('do_number')->unique();
                $table->foreignId('company_id')->constrained('companies');
                $table->foreignId('mine_id')->constrained('mines');
                $table->decimal('total_quantity', 10, 2);
                $table->decimal('remaining_quantity', 10, 2);
                $table->date('issue_date');
                $table->date('expiry_date');
                $table->enum('status', ['active', 'expired', 'completed', 'cancelled'])->default('active');
                $table->timestamps();
                
                // Indexes for better performance
                $table->index('do_number');
                $table->index('company_id');
                $table->index('mine_id');
                $table->index('status');
                $table->index('issue_date');
                $table->index('expiry_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
