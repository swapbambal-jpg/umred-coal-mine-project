<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Menu::with(['children', 'parent']);

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        if ($request->boolean('root_only')) {
            $query->whereNull('parent_id');
        }

        $menus = $query->orderBy('sort_order')->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $menus,
            'message' => 'Menus retrieved successfully'
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:menus,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $menu = Menu::create([
            'name' => $request->name,
            'url' => $request->url,
            'icon' => $request->icon,
            'parent_id' => $request->parent_id,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active
        ]);

        return response()->json([
            'success' => true,
            'data' => $menu->load(['children', 'parent']),
            'message' => 'Menu created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $menu->load(['children', 'parent']),
            'message' => 'Menu retrieved successfully'
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:menus,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $menu->update([
            'name' => $request->name,
            'url' => $request->url,
            'icon' => $request->icon,
            'parent_id' => $request->parent_id,
            'sort_order' => $request->sort_order ?? $menu->sort_order,
            'is_active' => $request->is_active
        ]);

        return response()->json([
            'success' => true,
            'data' => $menu->load(['children', 'parent']),
            'message' => 'Menu updated successfully'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found'
            ], 404);
        }

        if ($menu->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete menu with child items. Please delete or move children first.'
            ], 422);
        }

        $menu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully'
        ], 200);
    }

    /**
     * Get menu tree structure
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function tree(Request $request)
    {
        $query = Menu::whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->where('is_active', true)->with(['children']);
            }]);

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        $menus = $query->orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $menus,
            'message' => 'Menu tree retrieved successfully'
        ], 200);
    }

    /**
     * Toggle menu status
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus($id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found'
            ], 404);
        }

        $menu->update(['is_active' => !$menu->is_active]);

        return response()->json([
            'success' => true,
            'data' => $menu,
            'message' => "Menu " . ($menu->is_active ? 'activated' : 'deactivated') . " successfully"
        ], 200);
    }

    /**
     * Get parent menu list
     *
     * @return \Illuminate\Http\Response
     */
    public function getParentMenuList()
    {
        $parentMenus = Menu::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $parentMenus,
            'message' => 'Parent menus retrieved successfully'
        ], 200);
    }

    /**
     * Get parent and submenu list
     *
     * @param  int  $roleId
     * @return \Illuminate\Http\Response
     */
    public function getParentSubmenuList($roleId=1)
    {
        $parentMenus = Menu::whereNull('parent_id')
            ->where('is_active', true)
            ->leftJoin('role_menu_permissions', function($join) use ($roleId) {
                $join->on('menus.id', '=', 'role_menu_permissions.menu_id')
                     ->where('role_menu_permissions.role_id', '=', $roleId);
            })
            ->with(['children' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->select('id', 'name', 'parent_id', 'route', 'icon');
            }])
            ->groupBy('menus.id', 'menus.name', 'menus.route', 'menus.icon', 'role_menu_permissions.is_parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->select('menus.id', 'menus.name', 'menus.route', 'menus.icon', 'role_menu_permissions.is_parent')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $parentMenus,
            'message' => 'Parent and submenu list retrieved successfully'
        ], 200);
    }

    /**
     * Get complete menu tree with all levels
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getCompleteTree(Request $request)
    {
        $roleId = $request->role_id;
        
        // Validate role_id is provided and exists
        if (!$roleId) {
            return response()->json([
                'success' => false,
                'message' => 'Role ID is required',
                'data' => []
            ], 422);
        }
        
        // If role_id is 1 (super admin), get all menus without permission filtering
        if ($roleId == 1) {
            $menus = Menu::whereNull('parent_id')
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)
                        ->with(['children' => function ($subQuery) {
                            $subQuery->where('is_active', true)
                                ->with(['children' => function ($deepQuery) {
                                    $deepQuery->where('is_active', true);
                                }]);
                        }]);
                }]);
        } else {
            // For other roles, apply permission filtering
            $menus = Menu::whereNull('parent_id')
                ->where(function($query) use ($roleId) {
                    $query->whereHas('children', function($childQuery) use ($roleId) {
                        $childQuery->whereHas('roleMenuPermissions', function($permQuery) use ($roleId) {
                            $permQuery->where('role_id', $roleId)
                                    ->where('can_view', true);
                        });
                    })
                    ->orWhereHas('roleMenuPermissions', function($permQuery) use ($roleId) {
                        $permQuery->where('role_id', $roleId)
                                ->where('can_view', true);
                    });
                })
                ->with(['children' => function ($query) use ($roleId) {
                    $query->where('is_active', true)
                        ->whereHas('roleMenuPermissions', function($permQuery) use ($roleId) {
                            $permQuery->where('role_id', $roleId)
                                    ->where('can_view', true);
                        })
                        ->with(['roleMenuPermissions' => function($permQuery) use ($roleId) {
                            $permQuery->where('role_id', $roleId)
                                    ->select('menu_id', 'can_view', 'can_add', 'can_edit', 'can_delete');
                        }])
                        ->with(['children' => function ($subQuery) use ($roleId) {
                            $subQuery->where('is_active', true)
                                ->whereHas('roleMenuPermissions', function($permQuery) use ($roleId) {
                                    $permQuery->where('role_id', $roleId)
                                            ->where('can_view', true);
                                })
                                ->with(['roleMenuPermissions' => function($permQuery) use ($roleId) {
                                    $permQuery->where('role_id', $roleId)
                                            ->select('menu_id', 'can_view', 'can_add', 'can_edit', 'can_delete');
                                }])
                                ->with(['children' => function ($deepQuery) use ($roleId) {
                                    $deepQuery->where('is_active', true)
                                        ->whereHas('roleMenuPermissions', function($permQuery) use ($roleId) {
                                            $permQuery->where('role_id', $roleId)
                                                    ->where('can_view', true);
                                        })
                                        ->with(['roleMenuPermissions' => function($permQuery) use ($roleId) {
                                            $permQuery->where('role_id', $roleId)
                                                    ->select('menu_id', 'can_view', 'can_add', 'can_edit', 'can_delete');
                                        }]);
                                }]);
                        }]);
                }]);
        }

        if ($request->boolean('active_only', true)) {
            $menus->where('is_active', true);
        }

        $menuTree = $menus->orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $menuTree,
            'message' => 'Complete menu tree retrieved successfully'
        ], 200);
    }

    /**
     * Get menu subtree with all descendants
     *
     * @param  int  $menuId
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getSubtree($menuId, Request $request)
    {
        $menu = Menu::find($menuId);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found'
            ], 404);
        }

        $subtree = Menu::with(['children' => function ($query) {
                $query->where('is_active', true)
                    ->with(['children' => function ($subQuery) {
                        $subQuery->where('is_active', true)
                            ->with(['children' => function ($deepQuery) {
                                $deepQuery->where('is_active', true);
                            }]);
                    }]);
            }])
            ->where('id', $menuId)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $subtree,
            'message' => 'Menu subtree retrieved successfully'
        ], 200);
    }

    /**
     * Get direct children of a parent menu
     *
     * @param  int  $parentId
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getChildren($parentId, Request $request)
    {
        if ($parentId == 0) {
            // Get root level menus
            $children = Menu::whereNull('parent_id');
        } else {
            // Validate parent exists
            $parent = Menu::find($parentId);
            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent menu not found'
                ], 404);
            }

            $children = Menu::where('parent_id', $parentId);
        }

        if ($request->boolean('active_only', true)) {
            $children->where('is_active', true);
        }

        $menuChildren = $children->orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $menuChildren,
            'message' => 'Menu children retrieved successfully'
        ], 200);
    }

    /**
     * Get all ancestors of a menu (path to root)
     *
     * @param  int  $menuId
     * @return \Illuminate\Http\Response
     */
    public function getAncestors($menuId)
    {
        $menu = Menu::find($menuId);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found'
            ], 404);
        }

        $ancestors = [];
        $current = $menu;

        while ($current && $current->parent_id) {
            $parent = Menu::find($current->parent_id);
            if ($parent) {
                array_unshift($ancestors, $parent);
            }
            $current = $parent;
        }

        return response()->json([
            'success' => true,
            'data' => $ancestors,
            'message' => 'Menu ancestors retrieved successfully'
        ], 200);
    }

    /**
     * Get all descendants of a menu (children and sub-children)
     *
     * @param  int  $menuId
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getDescendants($menuId, Request $request)
    {
        $menu = Menu::find($menuId);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found'
            ], 404);
        }

        $descendants = $this->getAllDescendants($menuId);

        if ($request->boolean('active_only', true)) {
            $descendants = $descendants->where('is_active', true);
        }

        return response()->json([
            'success' => true,
            'data' => $descendants->values(),
            'message' => 'Menu descendants retrieved successfully'
        ], 200);
    }

    /**
     * Get menu breadcrumb path
     *
     * @param  int  $menuId
     * @return \Illuminate\Http\Response
     */
    public function getBreadcrumb($menuId)
    {
        $menu = Menu::find($menuId);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found'
            ], 404);
        }

        $breadcrumb = [];
        $current = $menu;

        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current->id,
                'name' => $current->name,
                'route' => $current->route
            ]);
            $current = $current->parent_id ? Menu::find($current->parent_id) : null;
        }

        return response()->json([
            'success' => true,
            'data' => $breadcrumb,
            'message' => 'Menu breadcrumb retrieved successfully'
        ], 200);
    }

    /**
     * Move menu to new parent
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $menuId
     * @return \Illuminate\Http\Response
     */
    public function moveMenu(Request $request, $menuId)
    {
        $menu = Menu::find($menuId);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'new_parent_id' => 'nullable|exists:menus,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $newParentId = $request->new_parent_id;

        // Prevent moving menu to itself or its descendants
        if ($newParentId) {
            if ($newParentId == $menuId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu cannot be its own parent'
                ], 422);
            }

            $descendants = $this->getAllDescendants($menuId);
            if ($descendants->contains('id', $newParentId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot move menu to its own descendant'
                ], 422);
            }
        }

        $menu->update(['parent_id' => $newParentId]);

        return response()->json([
            'success' => true,
            'data' => $menu->load(['children', 'parent']),
            'message' => 'Menu moved successfully'
        ], 200);
    }

    /**
     * Delete menu with all descendants
     *
     * @param  int  $menuId
     * @return \Illuminate\Http\Response
     */
    public function destroyWithChildren($menuId)
    {
        $menu = Menu::find($menuId);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found'
            ], 404);
        }

        // Get all descendants to delete
        $descendants = $this->getAllDescendants($menuId);
        
        // Delete all descendants first
        foreach ($descendants as $descendant) {
            $descendant->delete();
        }
        
        // Delete the menu itself
        $menu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu and all its descendants deleted successfully'
        ], 200);
    }

    /**
     * Helper method to get all descendants recursively
     *
     * @param  int  $parentId
     * @return \Illuminate\Support\Collection
     */
    private function getAllDescendants($parentId)
    {
        $descendants = collect();
        $children = Menu::where('parent_id', $parentId)->get();

        foreach ($children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($this->getAllDescendants($child->id));
        }

        return $descendants;
    }
}
