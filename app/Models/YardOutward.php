<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YardOutward extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'mine_id',
        'company_id',
        'yard_id',
        'source_id',
        'grade_id',
        'size_id',
        'total_trip',
        'challane_number',
        'truck_number',
        'driver_name',
        'truck_owner_name',
        'gross_weight',
        'tare_weight',
        'buyer_name',
        'netweight',
        'balance_quantity',
        'destination_name',
        'transporter_name',
        'balance_quantity_yard',
        'challange_number',
        'type_of_coal_id',
        'mode_of_dispatch_id',
        'do_id',
        'do_number',
        'do_quantity',
        'location',
        'party_name',
        'purchaser_name',
        'yard_challan_number',
        'delivery_challan_number',
        'sorted_exess',
        'total_quantity_received',
        'total_trips',
        'do_truck_owner_name',
        'do_truck_number',
        'do_accumulated_qty',
        'do_total_quantity',
        'company_name',
        'mine_name',
        'yard_locations_name_id'
    ];

    protected $casts = [
        'gross_weight' => 'decimal:2',
        'tare_weight' => 'decimal:2',
        'netweight' => 'decimal:2',
        'sorted_exess' => 'decimal:2',
        'total_quantity_received' => 'decimal:2',
        'balance_quantity' => 'decimal:2',
        'balance_quantity_yard' => 'decimal:2',
        'do_accumulated_qty' => 'decimal:2',
        'do_total_quantity' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the trip that owns the yard outward.
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

    public function yard()
    {
        return $this->belongsTo(Yard::class);
    }

    public function source()
    {
        return $this->belongsTo(YardSource::class);
    }

    public function grade()
    {
        return $this->belongsTo(DoGrade::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }

    public function modeOfDispatch()
    {
        return $this->belongsTo(ModeOfDispatche::class, 'mode_of_dispatch_id');
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, 'do_id');
    }

    public function yardLocation()
    {
        return $this->belongsTo(YardLocationsName::class, 'yard_locations_name_id');
    }

    /**
     * Get the trucks associated with the yard outward.
     */
    public function trucks()
    {
        return $this->hasMany(Truck::class);
    }
}
