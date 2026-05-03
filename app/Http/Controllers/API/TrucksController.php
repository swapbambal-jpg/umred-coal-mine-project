<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TrucksController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $trucks = Truck::all();
        return response()->json([
            'success' => true,
            'data' => $trucks,
            'message' => 'Trucks retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'truck_number' => 'required|string|max:255'
            ]);

            $truck = Truck::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $truck,
                'message' => 'Truck created successfully'
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
                'message' => 'Failed to create truck',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $truck = Truck::find($id);

        if (!$truck) {
            return response()->json([
                'success' => false,
                'message' => 'Truck not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $truck,
            'message' => 'Truck retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $truck = Truck::find($id);

        if (!$truck) {
            return response()->json([
                'success' => false,
                'message' => 'Truck not found'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'truck_number' => 'required|string|max:255'
            ]);

            $truck->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $truck,
                'message' => 'Truck updated successfully'
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
                'message' => 'Failed to update truck',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get truck details by truck model.
     */
    public function getTruckDetailsByModel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model_name' => 'required|string|max:255',
        ]);

        $trucks = Truck::where('mode_name', $validated['model_name'])->get();

        if ($trucks->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No trucks found with this model'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $trucks,
            'message' => 'Trucks retrieved successfully by model'
        ]);
    }

    /**
     * Get gate details by vehicle number.
     */
    public function getGateDetailsByVehicleNo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_no' => 'required|string|max:255',
        ]);

        $truck = Truck::where('truck_name', $validated['vehicle_no'])->first();

        if (!$truck) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $truck,
            'message' => 'Gate details retrieved successfully'
        ]);
    }

    /**
     * Get truck list for dropdown (ID and name only).
     */
    public function getTruckList(): JsonResponse
    {
        $trucks = Truck::select('id', 'truck_number as name')
            //->whereNotNull('truck_name')
            //->where('truck_name', '!=', '')
            ->orderBy('truck_name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trucks,
            'message' => 'Truck list retrieved successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $truck = Truck::find($id);

        if (!$truck) {
            return response()->json([
                'success' => false,
                'message' => 'Truck not found'
            ], 404);
        }

        try {
            $truck->delete();

            return response()->json([
                'success' => true,
                'message' => 'Truck deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete truck',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
