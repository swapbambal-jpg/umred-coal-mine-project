<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'plant_challan_number',
        'delivery_challan_number',
        'gross_weight',
        'tare_weight',
        'netweight',
        'sorted_exess',
        'total_quantity_received',
        'total_trips',
        'do_truck_owner_name',
        'do_truck_number',
        'do_accumulated_qty',
        'do_total_quantity',
        'company_name',
        'mine_name',
        'type_of_mode_id',
        'do_id',
        'do_number',
        'do_total_quantity',
        'do_accumulated_qty',
        'do_total_trips',
        'do_truck_number',
        'do_truck_owner_name',
        'truck_number',
        'driver_name',
        'truck_owner_name',
        'remaining_quantity',
        'truck_owner_name',
        'destination_name',
        'difference',
        'plant_received_date',
        'plant_attachment'
    ];

    protected $casts = [
        'gross_weight' => 'decimal:2',
        'tare_weight' => 'decimal:2',
        'netweight' => 'decimal:2',
        'total_quantity_recieved' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the trip that owns the plant.
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function mine()
    {
        return $this->belongsTo(Mine::class);
    }

    public function do()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    /**
     * Get the trucks associated with the plant.
     */
    public function trucks()
    {
        return $this->hasMany(Truck::class);
    }

    public function type_of_mode()
    {
        return $this->belongsTo(TypeOfMode::class);
    }

}
