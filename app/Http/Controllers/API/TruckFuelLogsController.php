<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TruckFuelLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TruckFuelLogsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $fuelLogs = TruckFuelLog::with(['truck'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $fuelLogs->items(),
            'pagination' => [
                'current_page' => $fuelLogs->currentPage(),
                'per_page' => $fuelLogs->perPage(),
                'total' => $fuelLogs->total(),
                'last_page' => $fuelLogs->lastPage(),
                'from' => $fuelLogs->firstItem(),
                'to' => $fuelLogs->lastItem()
            ],
            'message' => 'Truck fuel logs retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'truck_id' => 'required|exists:trucks,id',
                'diesel_rate' => 'required|numeric|min:0',
                'diesel_qty' => 'required|numeric|min:0',
                'old_km' => 'nullable|numeric|min:0',
                'new_km' => 'nullable|numeric|min:0',
                'actual_km' => 'nullable|numeric|min:0',
                'average_km' => 'nullable|numeric|min:0',
                'diesel_pump' => 'nullable|string|max:255',
                'del_amount' => 'nullable|numeric|min:0',
                'adblue_amount' => 'nullable|numeric|min:0',
                'adblue_party_name' => 'nullable|string|max:255',
                'advance_amount' => 'nullable|numeric|min:0'
            ]);

            $fuelLog = TruckFuelLog::create($validated);

            return response()->json([
                'success' => true,
                'data' => $fuelLog->load(['truck']),
                'message' => 'Truck fuel log created successfully'
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
                'message' => 'Failed to create truck fuel log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $fuelLog = TruckFuelLog::with(['truck'])->find($id);

        if (!$fuelLog) {
            return response()->json([
                'success' => false,
                'message' => 'Truck fuel log not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $fuelLog,
            'message' => 'Truck fuel log retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $fuelLog = TruckFuelLog::find($id);

        if (!$fuelLog) {
            return response()->json([
                'success' => false,
                'message' => 'Truck fuel log not found'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'truck_id' => 'required|exists:trucks,id',
                'diesel_rate' => 'required|numeric|min:0',
                'diesel_qty' => 'required|numeric|min:0',
                'old_km' => 'nullable|numeric|min:0',
                'new_km' => 'nullable|numeric|min:0',
                'actual_km' => 'nullable|numeric|min:0',
                'average_km' => 'nullable|numeric|min:0',
                'diesel_pump' => 'nullable|string|max:255',
                'del_amount' => 'nullable|numeric|min:0',
                'adblue_amount' => 'nullable|numeric|min:0',
                'adblue_party_name' => 'nullable|string|max:255',
                'advance_amount' => 'nullable|numeric|min:0'
            ]);

            $fuelLog->update($validated);

            return response()->json([
                'success' => true,
                'data' => $fuelLog->load(['truck']),
                'message' => 'Truck fuel log updated successfully'
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
                'message' => 'Failed to update truck fuel log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $fuelLog = TruckFuelLog::find($id);

        if (!$fuelLog) {
            return response()->json([
                'success' => false,
                'message' => 'Truck fuel log not found'
            ], 404);
        }

        try {
            $fuelLog->delete();

            return response()->json([
                'success' => true,
                'message' => 'Truck fuel log deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete truck fuel log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fuel logs by truck ID.
     */
    public function getFuelLogsByTruck(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'truck_id' => 'required|exists:trucks,id'
        ]);

        $fuelLogs = TruckFuelLog::with(['truck'])
            ->where('truck_id', $validated['truck_id'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $fuelLogs,
            'message' => 'Truck fuel logs retrieved successfully by truck'
        ]);
    }
}
