<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoGrade extends Model
{
    use HasFactory;

    protected $table = 'do_grades';

    protected $fillable = [
        'name',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => 'string'
    ];

    /**
     * Scope to get only active grades
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only inactive grades
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}
