<?php
  
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'ml_size',
        'selling_price',
        'mrp_price',
        'quanity'
    ];

     // Relationship with Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
