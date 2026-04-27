<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SizesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $sizes = Size::all();
        return response()->json([
            'success' => true,
            'data' => $sizes,
            'message' => 'Sizes retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|max:255',
                'status' => 'string'
            ]);

            $size = Size::create($validated);

            return response()->json([
                'success' => true,
                'data' => $size,
                'message' => 'Size created successfully'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create size',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $size = Size::find($id);

        if (!$size) {
            return response()->json([
                'success' => false,
                'message' => 'Size not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $size,
            'message' => 'Size retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $size = Size::find($id);

        if (!$size) {
            return response()->json([
                'success' => false,
                'message' => 'Size not found'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'status' => 'string'
            ]);

            $size->update($validated);

            return response()->json([
                'success' => true,
                'data' => $size,
                'message' => 'Size updated successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update size',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $size = Size::find($id);

        if (!$size) {
            return response()->json([
                'success' => false,
                'message' => 'Size not found'
            ], 404);
        }

        try {
            $size->delete();

            return response()->json([
                'success' => true,
                'message' => 'Size deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete size',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active sizes list.
     */
    public function getSizesList(): JsonResponse
    {
        $sizes = Size::where('status', true)->get();
        
        return response()->json([
            'success' => true,
            'data' => $sizes,
            'message' => 'Active sizes retrieved successfully'
        ]);
    }
}
