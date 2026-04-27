<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoProgressiveQuantity extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id',
        'accumulated_qty',
    ];

    protected $casts = [
        'accumulated_qty' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the delivery order that owns the progressive quantity.
     */
    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    /**
     * Scope to get progressive quantities for a specific delivery order
     */
    public function scopeForDeliveryOrder($query, $deliveryOrderId)
    {
        return $query->where('delivery_order_id', $deliveryOrderId);
    }

    /**
     * Scope to get records ordered by creation date (newest first)
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
