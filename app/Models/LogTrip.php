<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_trip_id',
        'do_id',
        'type_of_mode_id',
        'company_id',
        'mine_id',
        'operator_id',
        'driver_name',
        'truck_number',
        'tare_weight',
        'gross_weight',
        'netweight',
        'lifted_quantity',
        'remaining_quantity',
        'trip_date',
        'entry_status',
        'total_trips',
        'accumulated_qty',
        'truck_owner_name',
        'delivery_challan_number',
        'cil_subsidiary',
        'type_of_coal',
        'grad_name',
        'size_name',
        'destination_name',
        'rr_weight',
        'fnr_number',
        'chargeble_weight',
        'difference',
        'over_load',
        'penalty',
        'no_of_wagons',
        'loaded_wagons',
        'total_loaded',
        'total_balance',
        'stick_wagons',
        'type_of_wagons',
        'deleted_by',
        'delete_reason',
        'deleted_at'
    ];

    protected $casts = [
        'tare_weight' => 'decimal:2',
        'gross_weight' => 'decimal:2',
        'netweight' => 'decimal:2',
        'lifted_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'trip_date' => 'date',
        'accumulated_qty' => 'decimal:2',
        'rr_weight' => 'decimal:2',
        'chargeble_weight' => 'decimal:2',
        'difference' => 'decimal:2',
        'over_load' => 'decimal:2',
        'penalty' => 'decimal:2',
        'total_loaded' => 'decimal:2',
        'total_balance' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];
}
