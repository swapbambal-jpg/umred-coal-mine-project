<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Menu extends Model
{
    protected $fillable = [
        'name', 'route', 'icon', 'parent_id', 'sort_order', 'is_active'
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }

    public function permissions()
    {
        return $this->hasMany(RoleMenuPermission::class);
    }

    public function roleMenuPermissions()
    {
        return $this->hasMany(RoleMenuPermission::class, 'menu_id', 'id');
    }

    public function rolePermissions($roleId)
    {
        return $this->hasOne(RoleMenuPermission::class)
            ->where('role_id', $roleId)
            ->select('menu_id', 'can_view', 'can_add', 'can_edit', 'can_delete');
    }

    public function scopeWithHierarchyAndPermissions($query, $roleId)
    {
        return $query->where('parent_id', 0)
            ->with(['children' => function($childQuery) use ($roleId) {
                $childQuery->with(['rolePermissions' => function($permQuery) use ($roleId) {
                    $permQuery->select('menu_id', 'can_view', 'can_add', 'can_edit', 'can_delete');
                }])
                ->select('id', 'name', 'route', 'icon', 'parent_id');
            }])
            ->select('id', 'name', 'route', 'icon', 'parent_id');
    }
}
