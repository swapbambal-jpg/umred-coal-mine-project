<?php
  
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounterProductStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'ml_size',
        'selling_price',
        'mrp_price',
        'quanity'
    ];
}
