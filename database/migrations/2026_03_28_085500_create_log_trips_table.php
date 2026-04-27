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
        Schema::create('log_trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_trip_id'); // Reference to original trips table ID
            $table->unsignedBigInteger('do_id');
            $table->unsignedBigInteger('type_of_mode_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('mine_id');
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->string('driver_name');
            $table->string('truck_number');
            $table->decimal('tare_weight', 10, 2)->nullable();
            $table->decimal('gross_weight', 10, 2)->nullable();
            $table->decimal('netweight', 10, 2)->nullable();
            $table->decimal('lifted_quantity', 10, 2)->nullable();
            $table->decimal('remaining_quantity', 10, 2)->nullable();
            $table->date('trip_date');
            $table->string('entry_status')->nullable();
            $table->integer('total_trips')->nullable();
            $table->decimal('accumulated_qty', 10, 2)->nullable();
            $table->string('truck_owner_name')->nullable();
            $table->string('delivery_challan_number')->nullable();
            $table->string('cil_subsidiary')->nullable();
            $table->string('type_of_coal')->nullable();
            $table->string('grad_name')->nullable();
            $table->string('size_name')->nullable();
            $table->string('destination_name')->nullable();
            $table->decimal('rr_weight', 10, 2)->nullable();
            $table->string('fnr_number')->nullable();
            $table->decimal('chargeble_weight', 10, 2)->nullable();
            $table->decimal('difference', 10, 2)->nullable();
            $table->decimal('over_load', 10, 2)->nullable();
            $table->decimal('penalty', 10, 2)->nullable();
            $table->integer('no_of_wagons')->nullable();
            $table->integer('loaded_wagons')->nullable();
            $table->decimal('total_loaded', 10, 2)->nullable();
            $table->decimal('total_balance', 10, 2)->nullable();
            $table->integer('stick_wagons')->nullable();
            $table->string('type_of_wagons')->nullable();
            $table->string('deleted_by')->nullable(); // User who deleted the record
            $table->text('delete_reason')->nullable(); // Reason for deletion
            $table->timestamp('deleted_at'); // When the record was deleted
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('original_trip_id');
            $table->index('do_id');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_trips');
    }
};
