<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RoleMenuPermission;
use App\Models\Role;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class RoleMenuPermissionController extends Controller
{
    /**
     * Display a listing of the role menu permissions.
     */
    public function index(Request $request)
    {
        try {
            $query = RoleMenuPermission::with(['role:id,name', 'menu:id,name,route,icon,parent_id']);
            
            // Filter by role if provided
            if ($request->has('role_id') && !empty($request->role_id)) {
                $query->where('role_id', $request->role_id);
            }
            
            // Filter by menu if provided
            if ($request->has('menu_id') && !empty($request->menu_id)) {
                $query->where('menu_id', $request->menu_id);
            }
            
            // Filter by specific permission if provided
            if ($request->has('can_view')) {
                $query->where('can_view', $request->boolean('can_view'));
            }
            if ($request->has('can_add')) {
                $query->where('can_add', $request->boolean('can_add'));
            }
            if ($request->has('can_edit')) {
                $query->where('can_edit', $request->boolean('can_edit'));
            }
            if ($request->has('can_delete')) {
                $query->where('can_delete', $request->boolean('can_delete'));
            }
            
            // Pagination parameters - handle both query params and JSON body
            $requestData = $request->all();
            $page = isset($requestData['page']) ? $requestData['page'] : $request->get('page', 1);
            $limit = isset($requestData['offset']) ? $requestData['offset'] : $request->get('offset', 10);
            
            // Calculate offset based on page and limit
            $offset = ($page - 1) * $limit;
            
            // Get total count for pagination info
            $total = $query->count();
            
            // Apply pagination
            $roleMenuPermissions = $query->orderBy('created_at', 'desc')
                                       ->offset($offset)
                                       ->limit($limit)
                                       ->get();
            
            // Group by role_id in PHP and create single record per role
            $groupedPermissions = $roleMenuPermissions->groupBy('role_id')->map(function ($permissions, $roleId) {
                $firstPermission = $permissions->first();
                return [
                    'id' => $roleId,
                    'role' => $firstPermission->role,
                    'permissions' => $permissions->map(function ($perm) {
                        return [
                            'menu_id' => $perm->menu_id,
                            'menu' => $perm->menu,
                            'can_view' => $perm->can_view,
                            'can_add' => $perm->can_add,
                            'can_edit' => $perm->can_edit,
                            'can_delete' => $perm->can_delete
                        ];
                    })->toArray()
                ];
            })->values();
            
            return response()->json([
                'success' => true,
                'data' => $groupedPermissions,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'offset' => $offset,
                    'limit' => $limit,
                    'has_more' => ($offset + $limit) < $total,
                    'total_pages' => ceil($total / $limit)
                ],
                'message' => 'Role menu permissions retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Role menu permissions list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role menu permissions',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Store a newly created role menu permission in storage.
     */
    public function store(Request $request)
    {
        try {
            // Handle bulk update for menu permissions
            if ($request->has('menus') && $request->has('role_id')) {
                $role_id = $request->role_id;
                $menus = $request->menus;
                
                // Delete previous records for this role
                RoleMenuPermission::where('role_id', $role_id)->delete();
                
                foreach ($menus as $menu) {
                    $menuId = $menu['menu_id'];
                    
                    // Process parent menu (default permissions if not specified)
                    RoleMenuPermission::create([
                        'role_id' => $role_id,
                        'menu_id' => $menuId,
                        'can_view'   => isset($menu['can_view']) ? $menu['can_view'] : 0,
                        'can_add'    => isset($menu['can_add']) ? $menu['can_add'] : 0,
                        'can_edit'   => isset($menu['can_edit']) ? $menu['can_edit'] : 0,
                        'can_delete' => isset($menu['can_delete']) ? $menu['can_delete'] : 0,
                        'is_parent' => 1,
                    ]);

                    // Process children if they exist
                    if (isset($menu['children']) && is_array($menu['children'])) {
                        foreach ($menu['children'] as $childMenu) {
                            $childMenuId = $childMenu['menu_id'];
                            
                            RoleMenuPermission::create([
                                'role_id' => $role_id,
                                'menu_id' => $childMenuId,
                                'can_view'   => isset($childMenu['can_view']) ? $childMenu['can_view'] : 0,
                                'can_add'    => isset($childMenu['can_add']) ? $childMenu['can_add'] : 0,
                                'can_edit'   => isset($childMenu['can_edit']) ? $childMenu['can_edit'] : 0,
                                'can_delete' => isset($childMenu['can_delete']) ? $childMenu['can_delete'] : 0,
                            ]);
                        }
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Menu permissions updated successfully'
                ], 200);
            }
            
            // Handle single permission creation
            $validator = Validator::make($request->all(), [
                'role_id' => 'required|exists:roles,id',
                'menu_id' => 'required|exists:menus,id',
                'can_view' => 'required|boolean',
                'can_add' => 'required|boolean',
                'can_edit' => 'required|boolean',
                'can_delete' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if role menu permission already exists
            $existing = RoleMenuPermission::where('role_id', $request->role_id)
                                        ->where('menu_id', $request->menu_id)
                                        ->first();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role menu permission already exists for this role and menu',
                    'error' => 'Duplicate entry'
                ], 422);
            }

            $roleMenuPermission = RoleMenuPermission::create([
                'role_id' => $request->role_id,
                'menu_id' => $request->menu_id,
                'can_view' => $request->can_view,
                'can_add' => $request->can_add,
                'can_edit' => $request->can_edit,
                'can_delete' => $request->can_delete,
            ]);

            // Load the relationships for response
            $roleMenuPermission->load(['role:id,name', 'menu:id,name,route']);

            return response()->json([
                'success' => true,
                'data' => $roleMenuPermission,
                'message' => 'Role menu permission created successfully'
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Role menu permission creation error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role menu permission',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Display the specified role menu permission.
     */
    public function show($id)
    {
        try {
  
            $menuHierarchy = RoleMenuPermission::getMenuHierarchyByRole($id);

             
            return response()->json([
                'success' => true,
                "role_id" => $id,
                'data' => $menuHierarchy,
                'message' => 'Parent and submenu list retrieved successfully'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role menu permission not found',
                'error' => 'Role menu permission with ID ' . $id . ' not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Role menu permission show error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role menu permission',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Update the specified role menu permission in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Handle bulk update for menu permissions
            if ($request->has('menus') && $request->has('role_id')) {
                $role_id = $request->role_id;
                $menus = $request->menus;
                
                // Delete previous records for this role
                RoleMenuPermission::where('role_id', $role_id)->delete();
                
                foreach ($menus as $menu) {

                    $menuId = $menu['menu_id'];
                    
                    // Process parent menu (default permissions if not specified)
                    RoleMenuPermission::create([
                        'role_id' => $role_id,
                        'menu_id' => $menuId,
                        'can_view'   => isset($menu['can_view']) ? $menu['can_view'] : 0,
                        'can_add'    => isset($menu['can_add']) ? $menu['can_add'] : 0,
                        'can_edit'   => isset($menu['can_edit']) ? $menu['can_edit'] : 0,
                        'can_delete' => isset($menu['can_delete']) ? $menu['can_delete'] : 0,
                        'is_parent' => 1,
                    ]);

                    // Process children if they exist
                    if (isset($menu['children']) && is_array($menu['children'])) {
                        foreach ($menu['children'] as $childMenu) {
                            $childMenuId = $childMenu['menu_id'];
                            
                            RoleMenuPermission::create([
                                'role_id' => $role_id,
                                'menu_id' => $childMenuId,
                                'can_view'   => isset($childMenu['can_view']) ? $childMenu['can_view'] : 0,
                                'can_add'    => isset($childMenu['can_add']) ? $childMenu['can_add'] : 0,
                                'can_edit'   => isset($childMenu['can_edit']) ? $childMenu['can_edit'] : 0,
                                'can_delete' => isset($childMenu['can_delete']) ? $childMenu['can_delete'] : 0,
                            ]);
                        }
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Menu permissions updated successfully'
                ], 200);
            }
            
            // Handle single permission update
            $roleMenuPermission = RoleMenuPermission::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'role_id' => 'sometimes|required|exists:roles,id',
                'menu_id' => 'sometimes|required|exists:menus,id',
                'can_view' => 'sometimes|required|boolean',
                'can_add' => 'sometimes|required|boolean',
                'can_edit' => 'sometimes|required|boolean',
                'can_delete' => 'sometimes|required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate role_id and menu_id if being updated
            if (($request->has('role_id') && $request->role_id !== $roleMenuPermission->role_id) ||
                ($request->has('menu_id') && $request->menu_id !== $roleMenuPermission->menu_id)) {
                $exists = RoleMenuPermission::where('role_id', $request->role_id ?? $roleMenuPermission->role_id)
                                          ->where('menu_id', $request->menu_id ?? $roleMenuPermission->menu_id)
                                          ->where('id', '!=', $id)
                                          ->exists();
                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Role menu permission already exists for this role and menu',
                        'error' => 'Duplicate entry'
                    ], 422);
                }
            }

            $roleMenuPermission->update($request->all());

            // Load the relationships for response
            $roleMenuPermission->load(['role:id,name', 'menu:id,name,route']);

            return response()->json([
                'success' => true,
                'data' => $roleMenuPermission,
                'message' => 'Role menu permission updated successfully'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role menu permission not found',
                'error' => 'Role menu permission with ID ' . $id . ' not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Role menu permission update error', [
                'error' => $e->getMessage(),
                'id' => $id,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role menu permission',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Remove all role menu permissions for the specified role from storage.
     */
    public function destroy($roleId)
    {
        try {
            // Check if role exists
            $role = Role::findOrFail($roleId);
            
            // Delete all role menu permissions for this role
            $deletedCount = RoleMenuPermission::where('role_id', $roleId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'All role menu permissions deleted successfully',
                'deleted_count' => $deletedCount
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
                'error' => 'Role with ID ' . $roleId . ' not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Role menu permissions deletion error', [
                'error' => $e->getMessage(),
                'role_id' => $roleId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role menu permissions',
                'error' => 'Database error'
            ], 500);
        }
    }
        
    /**
     * Get role menu permissions list for dropdown
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getRoleMenuPermissionList(Request $request)
    {
        try {
            $query = RoleMenuPermission::select('id', 'role_id', 'menu_id', 'can_view', 'can_add', 'can_edit', 'can_delete')
                                     ->with(['role:id,name', 'menu:id,name,route']);
            
            // Filter by role if provided
            if ($request->has('role_id') && !empty($request->role_id)) {
                $query->where('role_id', $request->role_id);
            }
            
            // Filter by menu if provided
            if ($request->has('menu_id') && !empty($request->menu_id)) {
                $query->where('menu_id', $request->menu_id);
            }
            
            $roleMenuPermissions = $query->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $roleMenuPermissions,
                'message' => 'Role menu permission list retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Role menu permission dropdown list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role menu permission list',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Get menu permissions by role ID
     *
     * @param  int  $roleId
     * @return \Illuminate\Http\Response
     */
    public function getPermissionsByRole($roleId)
    {
        try {
            $roleMenuPermissions = RoleMenuPermission::with(['role:id,name', 'menu:id,name,route,icon,parent_id'])
                                                   ->where('role_id', $roleId)
                                                   ->get();
            
            return response()->json([
                'success' => true,
                'data' => $roleMenuPermissions,
                'message' => 'Role menu permissions retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Get permissions by role error', [
                'error' => $e->getMessage(),
                'role_id' => $roleId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role menu permissions',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Get all menus with permissions for a role
     *
     * @param  int  $roleId
     * @return \Illuminate\Http\Response
     */
    public function getMenusWithPermissions($roleId)
    {
        try {
            // Get all menus
            $menus = Menu::with(['children', 'permissions' => function($query) use ($roleId) {
                $query->where('role_id', $roleId);
            }])->whereNull('parent_id')->get();
            
            return response()->json([
                'success' => true,
                'data' => $menus,
                'message' => 'Menus with permissions retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Get menus with permissions error', [
                'error' => $e->getMessage(),
                'role_id' => $roleId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve menus with permissions',
                'error' => 'Database error'
            ], 500);
        }
    }
}
