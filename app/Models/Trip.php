<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
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
        'driver_name',
        'delivery_challan_number',
        'truck_number',
        'truck_owner_name',
        'tare_weight',
        'gross_weight',
        'netweight',
        'lifted_quantity',
        'remaining_quantity',
        'accumulated_qty',
        'total_trips',
        'trip_date',
        'destination_name',
        'freigh_amount',
        'gst_amount',
        'total_freigh_amount',
        'entry_status',
        'rr_number',
        'chargeble_quantity',
        'reject_wagons',
        'no_of_wagons_supply',
        'dispatch_siding_name',
        'dispatch_siding_code',
        'destination_siding_name',
        'destination_siding_code',
        'type_of_wagon_id',
        'rr_date',
        'total_quantity',
        'do_actual_qty',
        'trip_attachment'
    ];

    protected $casts = [
        'tare_weight' => 'decimal:2',
        'gross_weight' => 'decimal:2',
        'netweight' => 'decimal:2',
        'lifted_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2'
    ];

    /**
     * Get the delivery order that owns the trip.
     */
    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, 'do_id');
    }

    /**
     * Get the company that owns the trip.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the operator (user) that owns the trip.
     */
    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    
    /**
     * Get the mine that owns the trip.
     */
    public function mine()
    {
        return $this->belongsTo(Mine::class, 'mine_id');
    }

    
    /**
     * Get the operator (user) that owns the trip.
     */
    public function type_of_mode()
    {
        return $this->belongsTo(TypeOfMode::class, 'type_of_mode_id');
    }

    /**
     * Get the operator (user) that owns the trip.
     */
    public function type_of_wagon()
    {
        return $this->belongsTo(TypeOfWagon::class, 'type_of_wagon_id');
    }
}
