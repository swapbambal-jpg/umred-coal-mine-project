<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
                'order_id',
                'table_id',
                'category_id',
                'user_id',
                'product_id',
                'price',
                'quantity',
                "total_price",
                "payment_method",
                'ml_size',
                "status",
                "uuid"
            ];

    // Relationship with Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Relationship with Category
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
