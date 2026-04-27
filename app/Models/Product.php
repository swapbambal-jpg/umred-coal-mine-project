<?php
  
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'cat_id',
        'food_type',
        'restaurant_type',
        'name',
        'description',
        'image',
        'price',
        'mrp_price',
        'quantity',
        'unit'
    ];
    
    public function product_prices()
    {
        return $this->hasMany(ProductMlPrice::class, 'product_id', 'id');
    }

    public function categories()
    {
        return $this->belongsTo(Category::class, 'cat_id', 'id');
    }

    public function available_product_prices()
    {
        return $this->hasMany(CounterProductStock::class, 'product_id', 'id')
                    ->where('quanity', '>', 0);
    }

    public function counter_product_stocks()
    {
        return $this->hasMany(CounterProductStock::class, 'product_id', 'id')
                    ->where('quanity', '>', 0);
    }
}
