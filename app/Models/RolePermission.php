<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = [
        'role_id',
        'can_add',
        'can_view',
        'can_edit',
        'can_delete'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}

