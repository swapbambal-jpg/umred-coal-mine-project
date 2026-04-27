<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class RoleMenuPermission extends Model
{
    protected $fillable = [
        'role_id',
        'menu_id',
        'can_view',
        'can_add',
        'can_edit',
        'can_delete',
        'is_parent'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function menuWithChildrenAndPermissions()
    {
        return $this->belongsTo(Menu::class)
            ->with(['children' => function($query) {
                $query->with(['permissions' => function($permQuery) {
                    $permQuery->where('role_id', $this->role_id)
                             ->select('menu_id', 'can_view', 'can_add', 'can_edit', 'can_delete');
                }])
                ->select('id', 'name', 'route', 'icon', 'parent_id');
            }])
            ->select('id', 'name', 'route', 'icon', 'parent_id');
    }

    public function parentMenuWithPermissions()
    {
        return $this->belongsTo(Menu::class, 'menu_id')
            ->where('parent_id', 0)
            ->with(['children' => function($query) {
                $query->with(['permissions' => function($permQuery) {
                    $permQuery->where('role_id', $this->role_id)
                             ->select('menu_id', 'can_view', 'can_add', 'can_edit', 'can_delete');
                }])
                ->select('id', 'name', 'route', 'icon', 'parent_id');
            }])
            ->select('id', 'name', 'route', 'icon', 'parent_id');
    }

    public function scopeWithMenuHierarchy($query, $roleId)
    {
        return $query->where('role_id', $roleId)
            ->with(['menu' => function($menuQuery) {
                $menuQuery->where('parent_id', 0)
                    ->with(['children' => function($childQuery) {
                        $childQuery->with(['permissions' => function($permQuery) {
                            $permQuery->select('menu_id', 'can_view', 'can_add', 'can_edit', 'can_delete');
                        }])
                        ->select('id', 'name', 'route', 'icon', 'parent_id');
                    }])
                    ->select('id', 'name', 'route', 'icon', 'parent_id');
            }])
            ->with(['role:id,name']);
    }

    public function scopeGetMenuHierarchyWithPermissions($query, $roleId)
    {
        return $query->where('role_id', $roleId)
            ->with(['menu' => function($menuQuery) use ($roleId) {
                $menuQuery->with(['children' => function($childQuery) use ($roleId) {
                    $childQuery->with(['permissions' => function($permQuery) use ($roleId) {
                        $permQuery->where('role_id', $roleId)
                               ->select('menu_id', 'can_view', 'can_add', 'can_edit', 'can_delete');
                    }])
                    ->select('id', 'name', 'route', 'icon', 'parent_id');
                }])
                ->select('id', 'name', 'route', 'icon', 'parent_id');
            }])
            ->with(['role:id,name']);
    }

    public static function getMenuHierarchyByRole($roleId)
    {
        // Get all menu IDs that have permissions for this role
        $permittedMenuIds = RoleMenuPermission::where('role_id', $roleId)
            ->pluck('menu_id')
            ->toArray();

        // Get all menus (both parent and child) that have permissions
        $allPermittedMenus = Menu::whereIn('id', $permittedMenuIds)
            ->with(['permissions' => function($permQuery) use ($roleId) {
                $permQuery->where('role_id', $roleId)
                       ->select('menu_id', 'can_view', 'can_add', 'can_edit', 'can_delete', 'is_parent');
            }])
            ->select('id', 'name', 'route', 'icon', 'parent_id')
            ->get()
            ->keyBy('id');

        // Get is_parent values for each menu
        $isParentValues = RoleMenuPermission::where('role_id', $roleId)
            ->whereIn('menu_id', $permittedMenuIds)
            ->pluck('is_parent', 'menu_id');

        // Add is_parent to each menu
        foreach ($allPermittedMenus as $menu) {
            $menu->is_parent = $isParentValues->get($menu->id, 0);
        }

        // Group into hierarchy
        $parentMenus = collect();
        
        foreach ($allPermittedMenus as $menu) {
            if ($menu->parent_id == 0) {
                // This is a parent menu
                $menu->children = collect();
                $parentMenus->put($menu->id, $menu);
            } else {
                // This is a child menu, add to its parent if parent exists in permitted menus
                if ($allPermittedMenus->has($menu->parent_id)) {
                    $parent = $allPermittedMenus->get($menu->parent_id);
                    if (!$parentMenus->has($parent->id)) {
                        $parent->children = collect();
                        $parentMenus->put($parent->id, $parent);
                    }
                    $parentMenus->get($parent->id)->children->push($menu);
                }
            }
        }

        return $parentMenus->values();
    }
}
