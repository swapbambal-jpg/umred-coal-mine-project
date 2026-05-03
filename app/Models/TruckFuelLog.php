<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TruckFuelLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'truck_id',
        'diesel_rate',
        'diesel_qty',
        'diesel_amount',
        'old_km',
        'new_km',
        'actual_km',
        'average_km',
        'diesel_pump',
        'del_amount',
        'adblue_amount',
        'adblue_party_name',
        'advance_amount'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public function truck()
    {
        return $this->belongsTo(Truck::class, 'truck_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function dieselAccount()
    {
        return $this->belongsTo(User::class, 'diesel_account_id');
    }
}
