<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOfMode extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'status'
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

}
