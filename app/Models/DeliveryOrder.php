<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | DATABASE INDEXES
    |--------------------------------------------------------------------------
    |
    | The delivery_orders table has the following indexes for performance:
    | - do_number (index)
    | - company_id (index) - Foreign key to companies table
    | - mine_id (index) - Foreign key to mines table  
    | - status (index)
    | - issue_date (index)
    | - expiry_date (index)
    |
    */

    protected $fillable = [
        'do_number',
        'cil_id',
        'company_id',
        'mine_id',
        'total_quantity',
        'remaining_quantity',
        'issue_date',
        'expiry_date',
        'status',
        'grade_id',
        'type_of_purchase_id',
        'delivery_challan_number',
        'accumulated_qty',
        'do_area',
        'size_id',
        'type_of_mode_id',
        'type_of_coal_id',
        'party_name',
        'rr_date',
        'balance',
        'loaded',
        'destination_siding_name',
        'type_of_wagons',
        'stick_wagons',
        'loaded_wagon',
        'no_of_wagons',
        'penalty',
        'over_load',
        'rr_number',
        'fnr_number',
        'rr_weight',
        'chargeable_weight',
        'dispatch_siding_name',
        'dispatch_siding_code',
        'loading_siding_code',
        'loading_siding_name',
        'destination_siding_code',
        'destination_siding_name',
        'total_loaded',
        'total_balance',
        'no_of_rake_saction',
        'registration_number',
        'stacking_permission_hrs',
        'stacking_permission_upto_date',
        'no_of_rake_loaded',
        'number_of_rake_balance',
        'type_of_railway_id',
        'division_of_railway',
        'balance_quantity',
        'validatity_section_date',        
        'rake_sanction_quantity',
        'sanction_validity_date',
        'difference',
        'over_load_quantity',
        'no_of_wagons_supply',
        'reject_wagon',
        'total_loaded_quantity',
        'total_balance_quantity',
        'siding_stock_yard',
        'penalty_by_own',
        'penalty_by_company',
        'type_of_wagon_id',
        'destination_name',
        'do_actual_qty',
        'do_attachment'
    ];

    protected $casts = [
        'total_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the mine that owns the delivery order.
     * Uses mine_id (indexed) for optimal performance.
     */
    public function mine()
    {
        return $this->belongsTo(Mine::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }

    public function grade()
    {
        return $this->belongsTo(DoGrade::class);
    }

    public function type_of_mode()
    {
        return $this->belongsTo(TypeOfMode::class);
    }

    public function type_of_coal()
    {
        return $this->belongsTo(TypeOfCoal::class);
    }

    
    
    public function cil_company()
    {
         return $this->belongsTo(CilSubsidiaryCompany::class, 'cil_id', 'id');
    }


    public function type_of_purchase()
    {
        return $this->belongsTo(TypeOfPurchase::class);
    }


    /**
     * Get formatted issue date
     */
    public function getIssueDateAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : null;
    }

    /**
     * Get formatted expiry date
     */
    public function getExpiryDateAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : null;
    }

    /**
     * Get the company that owns the delivery order.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the trips for the delivery order.
     */
    public function trips()
    {
        return $this->hasMany(Trip::class, 'do_id');
    }

        public function plants()
    {
        return $this->hasMany(Plant::class, 'do_id');
    }


    /**
     * Scope to get only active delivery orders
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get expired delivery orders
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                    ->orWhere('expiry_date', '<', now());
    }

    /**
     * Scope to search by DO number
     */
    public function scopeSearchByDoNumber($query, $search)
    {
        return $query->where('do_number', 'like', '%' . $search . '%');
    }

    /**
     * Scope to filter by mine
     */
    public function scopeByMine($query, $mineId)
    {
        return $query->where('mine_id', $mineId);
    }

    /**
     * Scope to filter by company
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Check if delivery order is expired
     */
    public function isExpired()
    {
        return $this->expiry_date < now() || $this->status === 'expired';
    }

    /**
     * Check if delivery order has remaining quantity
     */
    public function hasRemainingQuantity()
    {
        return $this->remaining_quantity > 0;
    }
}
