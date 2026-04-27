<?php
  
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mine extends Model
{
    use HasFactory;

    protected $fillable = [
        'mine_name',
        'location',
        'status',
        'updated_at',
        'created_at'
    ];
    
    /**
     * Get the delivery orders for the mine.
     */
    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class);
    }
    
}
