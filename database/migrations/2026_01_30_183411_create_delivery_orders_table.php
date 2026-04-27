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
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('do_number', 100)->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('mine_id');
            $table->decimal('total_quantity', 15, 2);
            $table->decimal('remaining_quantity', 15, 2);
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->enum('status', ['active', 'expired', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index('do_number');
            $table->index('company_id');
            $table->index('mine_id');
            $table->index('status');
            $table->index('issue_date');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
