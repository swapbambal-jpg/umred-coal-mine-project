<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['table_id','category_id','user_id','product_id','price','quantity',"total_price","payment_method","status","partial_amount","total_amount","offline_status","uuid","fail_reason","discount","discount_amount","discount_type"];

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
    // Relationship with Category
    public function rest_table()
    {
        return $this->belongsTo(RestTable::class, 'table_id');
    }
    // Relationship with Category
    public function order_item()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}