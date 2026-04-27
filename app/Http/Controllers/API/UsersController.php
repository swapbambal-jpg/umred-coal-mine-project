<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsersController extends BaseController
{
    public function profile(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->sendError('Unauthorized', [], 401);
        }
        return $this->sendResponse($user, 'User profile retrieved successfully.');
    }

    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = User::query();
             $query->where('status','active');
            // Filter by role if provided
            if ($request->has('role_id') && !empty($request->role_id)) {
                $query->where('role_id', $request->role_id);
            }
            
            // Search by name, first_name, last_name, email if provided
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            }
            
            // Pagination parameters - handle both query params and JSON body
            $requestData = $request->all();
            $page = isset($requestData['page']) ? $requestData['page'] : $request->get('page', 1);
            $offset = isset($requestData['offset']) ? $requestData['offset'] : $request->get('offset', 0);
            $limit = isset($requestData['limit']) ? $requestData['limit'] : $request->get('limit', 10);
            
            // Calculate offset based on page if offset is not explicitly provided
            if ($offset == 0 && $page > 1) {
                $offset = ($page - 1) * $limit;
            }
            
            // Get total count for pagination info
            $total = $query->count();
            
            // Apply pagination
            $users = $query->with('role')
                          ->orderBy('created_at', 'desc')
                          ->offset($offset)
                          ->limit($limit)
                          ->get();
            
            // Calculate current page based on offset for response
            $currentPage = floor($offset / $limit) + 1;
            
            return response()->json([
                'success' => true,
                'data' => $users,
                'pagination' => [
                    'total' => $total,
                    'page' => $currentPage,
                    'offset' => $offset,
                    'limit' => $limit,
                    'has_more' => ($offset + $limit) < $total,
                    'total_pages' => ceil($total / $limit)
                ],
                'message' => 'Users retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('User list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'role_id' => 'required|exists:roles,id',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'mobile' => 'nullable|string|max:20',
                'password' => 'required|string|min:6',
                'adhar_number'=>'required|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'role_id' => $request->role_id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'mobile' => $request->mobile,
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User created successfully'
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('User creation error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $user = User::with('role')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User retrieved successfully'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => 'User with ID ' . $id . ' not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('User show error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Update the specified user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'role_id' => 'sometimes|required|exists:roles,id',
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $id,
                'phone' => 'sometimes|nullable|string|max:20',
                'mobile' => 'sometimes|nullable|string|max:20',
                'password' => 'sometimes|required|string|min:6',
                'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->all();
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                
                // Create directory if it doesn't exist
                $uploadPath = public_path('upload/users');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Move image to upload/users folder
                $image->move($uploadPath, $imageName);
                
                // Delete old image if exists
                if ($user->image && file_exists(public_path($user->image))) {
                    unlink(public_path($user->image));
                }
                
                // Update image field with new path
                $updateData['image'] = 'upload/users/' . $imageName;
            }
            
            // Hash password if provided
            if (isset($updateData['password'])) {
                $updateData['password'] = Hash::make($updateData['password']);
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User updated successfully'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => 'User with ID ' . $id . ' not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('User update error', [
                'error' => $e->getMessage(),
                'id' => $id,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'error' => 'User not authenticated'
                ], 401);
            }
            
            $validator = Validator::make($request->all(), [
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|nullable|string|max:20',
                'mobile' => 'sometimes|nullable|string|max:20',
                'password' => 'sometimes|required|string|min:6',
                'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->all();
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                
                // Create directory if it doesn't exist
                $uploadPath = public_path('upload/users');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Move image to upload/users folder
                $image->move($uploadPath, $imageName);
                
                // Delete old image if exists
                if ($user->image && file_exists(public_path($user->image))) {
                    unlink(public_path($user->image));
                }
                
                // Update image field with new path
                $updateData['image'] = 'upload/users/' . $imageName;
            }
            
            // Hash password if provided
            if (isset($updateData['password'])) {
                $updateData['password'] = Hash::make($updateData['password']);
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Profile updated successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Profile update error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            // $user = User::findOrFail($id);
            // $user->delete();

            // return response()->json([
            //     'success' => true,
            //     'message' => 'User deleted successfully'
            // ], 200);

            $user = User::findOrFail($id);
            $user->status = 'inactive'; // or 0 if using integer
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User marked as inactive successfully'
            ], 200);
            
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => 'User with ID ' . $id . ' not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('User deletion error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Get users list for dropdown (id and name only)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getUserList(Request $request)
    {
        try {
            $query = User::select('id', 'name', 'first_name', 'last_name', 'email');
            
            // Filter by role if provided
            if ($request->has('role_id') && !empty($request->role_id)) {
                $query->where('role_id', $request->role_id);
            }
            
            // Search by name if provided
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%');
                });
            }
            
            $users = $query->orderBy('name', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'User list retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('User dropdown list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user list',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Get roles list for dropdown (id and name only)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getRoleList(Request $request)
    {
        try {
            $query = \App\Models\Role::select('id', 'name');
            
            // Filter by status if provided
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }
            
            // Search by role name if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            // By default, only show active roles
            if (!$request->has('status')) {
                $query->where('status', 'active');
            }
            
            $roles = $query->orderBy('name', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $roles,
                'message' => 'Role list retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Role dropdown list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role list',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Get users for dropdown with optimized data structure
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getUserDropdown(Request $request)
    {
        try {
            $query = User::select('id', 'name', 'first_name', 'last_name', 'email', 'status');
            
            // Filter by role if provided
            if ($request->has('role_id') && !empty($request->role_id)) {
                $query->where('role_id', $request->role_id);
            }
            
            // Filter by status if provided (default to active users only)
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            } else {
                // By default, only show active users for dropdown
                $query->where('status', 'active');
            }
            
            // Search by name, first_name, last_name, email if provided
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            }
            
            // Limit results for dropdown performance
            $limit = $request->get('limit', 50);
            $users = $query->orderBy('name', 'asc')
                          ->limit($limit)
                          ->get();
            
            // Format dropdown data with display_name and value
            $formattedUsers = $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name ?: trim($user->first_name . ' ' . $user->last_name),
                    'full_name' => $user->name ?: trim($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email,
                    'status' => $user->status
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedUsers,
                'message' => 'User dropdown retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('User dropdown error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user dropdown',
                'error' => 'Database error'
            ], 500);
        }
    }
}