<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\YardLocationsName;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class YardLocationsNameController extends BaseController
{
    /**
     * Display a listing of the yard location names.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = YardLocationsName::query();
            
            // Filter by status if provided
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }
            
            // Search by name if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            // Pagination parameters
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 10);
            
            $page = $page > 0 ? $page : 1;
            $limit = $limit > 0 ? $limit : 10;
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $total = $query->count();
            
            // Apply pagination
            $yardLocations = $query->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $yardLocations,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'offset' => $offset,
                    'limit' => $limit,
                    'has_more' => ($offset + $limit) < $total,
                    'total_pages' => ceil($total / $limit)
                ],
                'message' => 'Yard location names retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Yard location names list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve yard location names',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Store a newly created yard location name in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:yard_locations_names,name',
                'status' => 'required|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $yardLocation = YardLocationsName::create($request->all());

            return $this->sendResponse($yardLocation, 'Yard location name created successfully.');
            
        } catch (\Exception $e) {
            Log::error('Yard location name creation error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to create yard location name.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified yard location name.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $yardLocation = YardLocationsName::findOrFail($id);
            
            return $this->sendResponse($yardLocation, 'Yard location name retrieved successfully.');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Yard location name not found.', ['error' => 'Yard location name with ID ' . $id . ' not found']);
            
        } catch (\Exception $e) {
            Log::error('Yard location name show error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return $this->sendError('An error occurred while retrieving the yard location name.', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
        }
    }

    /**
     * Update the specified yard location name in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $yardLocation = YardLocationsName::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:yard_locations_names,name,' . $id,
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $yardLocation->update($request->all());

            return $this->sendResponse($yardLocation, 'Yard location name updated successfully.');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Yard location name not found.', ['error' => 'Yard location name with ID ' . $id . ' not found']);
            
        } catch (\Exception $e) {
            Log::error('Yard location name update error', [
                'error' => $e->getMessage(),
                'id' => $id,
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to update yard location name.', ['error' => 'Database error']);
        }
    }

    /**
     * Remove the specified yard location name from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $yardLocation = YardLocationsName::findOrFail($id);
            $yardLocation->delete();

            return $this->sendResponse([], 'Yard location name deleted successfully.');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Yard location name not found.', ['error' => 'Yard location name with ID ' . $id . ' not found']);
            
        } catch (\Exception $e) {
            Log::error('Yard location name deletion error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return $this->sendError('Failed to delete yard location name.', ['error' => 'Database error']);
        }
    }

    /**
     * Get yard location names list for dropdown (id and name only)
     */
    public function getYardLocationsNameList(Request $request): JsonResponse
    {
        try {
            $query = YardLocationsName::select('id', 'name');
            
            // Search by name if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            // Filter by status if provided
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            } else {
                // By default, only show active records
                $query->where('status', 'active');
            }
            
            $yardLocations = $query->orderBy('name', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $yardLocations,
                'message' => 'Yard location names list retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Yard location names dropdown list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve yard location names list',
                'error' => 'Database error'
            ], 500);
        }
    }
}
