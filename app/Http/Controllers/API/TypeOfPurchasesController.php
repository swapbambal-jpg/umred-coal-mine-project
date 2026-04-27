<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\TypeOfPurchase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TypeOfPurchasesController extends BaseController
{
    /**
     * Display a listing of the type of purchases.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = TypeOfPurchase::query();
            
            // Search by name if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            $typeOfPurchases = $query->orderBy('name', 'asc')->get();
            
            return $this->sendResponse($typeOfPurchases, 'Type of purchases retrieved successfully.');
            
        } catch (\Exception $e) {
            Log::error('Type of purchases list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to retrieve type of purchases.', ['error' => 'Database error']);
        }
    }

    /**
     * Store a newly created type of purchase in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:type_of_purchases,name'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        try {
            $typeOfPurchase = TypeOfPurchase::create([
                'name' => $request->name
            ]);
            
            return $this->sendResponse($typeOfPurchase, 'Type of purchase created successfully.');
            
        } catch (\Exception $e) {
            Log::error('Type of purchase creation error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to create type of purchase.', ['error' => 'Database error']);
        }
    }

    /**
     * Display the specified type of purchase.
     */
    public function show($id): JsonResponse
    {
        try {
            $typeOfPurchase = TypeOfPurchase::find($id);
            
            if (!$typeOfPurchase) {
                return $this->sendError('Type of purchase not found.', ['error' => 'Type of purchase not found']);
            }
            
            return $this->sendResponse($typeOfPurchase, 'Type of purchase retrieved successfully.');
            
        } catch (\Exception $e) {
            Log::error('Type of purchase show error', [
                'error' => $e->getMessage(),
                'purchase_id' => $id
            ]);
            
            return $this->sendError('Failed to retrieve type of purchase.', ['error' => 'Database error']);
        }
    }

    /**
     * Update the specified type of purchase in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:type_of_purchases,name,' . $id
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        try {
            $typeOfPurchase = TypeOfPurchase::find($id);
            
            if (!$typeOfPurchase) {
                return $this->sendError('Type of purchase not found.', ['error' => 'Type of purchase not found']);
            }
            
            $typeOfPurchase->update($request->all());
            
            return $this->sendResponse($typeOfPurchase, 'Type of purchase updated successfully.');
            
        } catch (\Exception $e) {
            Log::error('Type of purchase update error', [
                'error' => $e->getMessage(),
                'purchase_id' => $id,
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to update type of purchase.', ['error' => 'Database error']);
        }
    }

    /**
     * Remove the specified type of purchase from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $typeOfPurchase = TypeOfPurchase::find($id);
            
            if (!$typeOfPurchase) {
                return $this->sendError('Type of purchase not found.', ['error' => 'Type of purchase not found']);
            }
            
            $typeOfPurchase->delete();
            
            return $this->sendResponse([], 'Type of purchase deleted successfully.');
            
        } catch (\Exception $e) {
            Log::error('Type of purchase deletion error', [
                'error' => $e->getMessage(),
                'purchase_id' => $id
            ]);
            
            return $this->sendError('Failed to delete type of purchase.', ['error' => 'Database error']);
        }
    }

    /**
     * Get type of purchases list for dropdown (id and name only)
     */
    public function getTypeOfPurchasesList(Request $request): JsonResponse
    {
        try {
            $query = TypeOfPurchase::select('id', 'name');
            
            // Search by name if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            $typeOfPurchases = $query->orderBy('name', 'asc')->get();
            
            return $this->sendResponse($typeOfPurchases, 'Type of purchases list retrieved successfully.');
            
        } catch (\Exception $e) {
            Log::error('Type of purchases dropdown list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to retrieve type of purchases list.', ['error' => 'Database error']);
        }
    }
}
