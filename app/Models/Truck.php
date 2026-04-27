<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    use HasFactory;

    protected $fillable = [
        'truck_name',
        'mode_name',
        'truck_number',
        'registration_date',
        'chassis_no',
        'engine_no',
        'insurance_company',
        'insurance_policy_no',
        'insurance_valid_up_to',
        'pucc_no',
        'pucc_valid_up_to',
        'netweight',
        'gross_weight',
        'tare_weight',
        'truck_permit_validity',
        'fitness_validity',
        'tax_valid_up_to',
        'insurance_policy_number',
        'truck_owner_name'
    ];

    protected $dates = [
        'registration_date',
        'insurance_valid_up_to',
        'pucc_valid_up_to',
        'truck_permit_validity',
        'fitness_validity',
        'tax_valid_up_to',
        'created_at',
        'updated_at',
    ];
}
