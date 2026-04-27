<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOfWagon extends Model
{
    use HasFactory;

    protected $table = 'type_of_wagons';

    protected $fillable = [
        'name'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Scope to get only active purchases (if status field exists in future)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only inactive purchases (if status field exists in future)
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}
