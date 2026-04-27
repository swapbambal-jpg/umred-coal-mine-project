<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
    protected $fillable = [
            'table_id',
            'category_id',
            'user_id',
            'product_id',
            'price',
            'quantity',
            "total_price",
            "restaurant_type",
            "product_ml_price_id"
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
    
    public function selected_product()
    {
        return $this->belongsTo(ProductMlPrice::class, 'product_ml_price_id');
    }
}
