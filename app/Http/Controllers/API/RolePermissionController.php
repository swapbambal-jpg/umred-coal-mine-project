<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RolePermission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class RolePermissionController extends Controller
{
    /**
     * Display a listing of the role permissions.
     */
    public function index(Request $request)
    {
        try {
            $query = RolePermission::with(['role:id,name']);
            
            // Filter by role if provided
            if ($request->has('role_id') && !empty($request->role_id)) {
                $query->where('role_id', $request->role_id);
            }
            
            // Filter by specific permission if provided
            if ($request->has('can_add')) {
                $query->where('can_add', $request->boolean('can_add'));
            }
            if ($request->has('can_view')) {
                $query->where('can_view', $request->boolean('can_view'));
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
            $rolePermissions = $query->orderBy('created_at', 'desc')
                                   ->offset($offset)
                                   ->limit($limit)
                                   ->get();
            
            return response()->json([
                'success' => true,
                'data' => $rolePermissions,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'offset' => $offset,
                    'limit' => $limit,
                    'has_more' => ($offset + $limit) < $total,
                    'total_pages' => ceil($total / $limit)
                ],
                'message' => 'Role permissions retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Role permissions list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role permissions',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Store a newly created role permission in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'role_id' => 'required|exists:roles,id',
                'can_add' => 'required|boolean',
                'can_view' => 'required|boolean',
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

            // Check if role permission already exists for this role
            $existing = RolePermission::where('role_id', $request->role_id)->first();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role permission already exists for this role',
                    'error' => 'Duplicate entry'
                ], 422);
            }

            $rolePermission = RolePermission::create([
                'role_id' => $request->role_id,
                'can_add' => $request->can_add,
                'can_view' => $request->can_view,
                'can_edit' => $request->can_edit,
                'can_delete' => $request->can_delete,
            ]);

            // Load the relationship for response
            $rolePermission->load(['role:id,name']);

            return response()->json([
                'success' => true,
                'data' => $rolePermission,
                'message' => 'Role permission created successfully'
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Role permission creation error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role permission',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Display the specified role permission.
     */
    public function show($id)
    {
        try {
            $rolePermission = RolePermission::with(['role:id,name'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $rolePermission,
                'message' => 'Role permission retrieved successfully'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role permission not found',
                'error' => 'Role permission with ID ' . $id . ' not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Role permission show error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role permission',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Update the specified role permission in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $rolePermission = RolePermission::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'role_id' => 'sometimes|required|exists:roles,id',
                'can_add' => 'sometimes|required|boolean',
                'can_view' => 'sometimes|required|boolean',
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

            // Check for duplicate role_id if being updated
            if ($request->has('role_id') && $request->role_id !== $rolePermission->role_id) {
                $exists = RolePermission::where('role_id', $request->role_id)
                                      ->where('id', '!=', $id)
                                      ->exists();
                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Role permission already exists for this role',
                        'error' => 'Duplicate entry'
                    ], 422);
                }
            }

            $rolePermission->update($request->all());

            // Load the relationship for response
            $rolePermission->load(['role:id,name']);

            return response()->json([
                'success' => true,
                'data' => $rolePermission,
                'message' => 'Role permission updated successfully'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role permission not found',
                'error' => 'Role permission with ID ' . $id . ' not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Role permission update error', [
                'error' => $e->getMessage(),
                'id' => $id,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role permission',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Remove the specified role permission from storage.
     */
    public function destroy($id)
    {
        try {
            $rolePermission = RolePermission::findOrFail($id);
            $rolePermission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role permission deleted successfully'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role permission not found',
                'error' => 'Role permission with ID ' . $id . ' not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Role permission deletion error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role permission',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Get role permissions list for dropdown (id and role info only)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getRolePermissionList(Request $request)
    {
        try {
            $query = RolePermission::select('id', 'role_id', 'can_add', 'can_view', 'can_edit', 'can_delete')
                                 ->with(['role:id,name']);
            
            // Filter by role if provided
            if ($request->has('role_id') && !empty($request->role_id)) {
                $query->where('role_id', $request->role_id);
            }
            
            // Filter by specific permission if provided
            if ($request->has('can_add')) {
                $query->where('can_add', $request->boolean('can_add'));
            }
            if ($request->has('can_view')) {
                $query->where('can_view', $request->boolean('can_view'));
            }
            if ($request->has('can_edit')) {
                $query->where('can_edit', $request->boolean('can_edit'));
            }
            if ($request->has('can_delete')) {
                $query->where('can_delete', $request->boolean('can_delete'));
            }
            
            $rolePermissions = $query->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $rolePermissions,
                'message' => 'Role permission list retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Role permission dropdown list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role permission list',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Get role permission by role ID
     *
     * @param  int  $roleId
     * @return \Illuminate\Http\Response
     */
    public function getPermissionByRole($roleId)
    {
        try {
            $rolePermission = RolePermission::with(['role:id,name'])
                                         ->where('role_id', $roleId)
                                         ->first();
            
            if (!$rolePermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'No permissions found for this role',
                    'data' => null
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $rolePermission,
                'message' => 'Role permissions retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Get permission by role error', [
                'error' => $e->getMessage(),
                'role_id' => $roleId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role permissions',
                'error' => 'Database error'
            ], 500);
        }
    }
}
