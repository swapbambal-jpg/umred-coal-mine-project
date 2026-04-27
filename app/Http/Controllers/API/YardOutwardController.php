<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\YardOutward;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class YardOutwardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = YardOutward::with([
                'trip', 
                'company:id,company_name', 
                'mine:id,mine_name', 
                'yard:id,yard_name',
                'source:id,name',
                'grade:id,name',
                'size:id,name',
                'modeOfDispatch:id,name',
                'deliveryOrder:id,do_number',
                'yardLocation:id,name',
            ]);
            $query->where('mode_of_dispatch_id', 2);
            
            
            $params = $request->input('params', []);

            if (!empty($params['do_date'])) {
              $query->whereDate('created_at',$params['do_date']);
            }

            if (!empty($params['do_id'])) {
              $query->where('id',$params['do_id']);
            }

            //Search by challan number if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('challane_number', 'like', '%' . $request->search . '%')
                      ->orWhere('yard_challan_number', 'like', '%' . $request->search . '%')
                      ->orWhere('delivery_challan_number', 'like', '%' . $request->search . '%');
            }
            
            // Filter by date range if provided
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Pagination parameters
            $requestData = $request->all();
            $page  = isset($requestData['params']['page']) 
                        ? (int) $requestData['params']['page'] 
                        : (int) $request->get('page', 1);

            $limit = isset($requestData['params']['limit']) 
                        ? (int) $requestData['params']['limit'] 
                        : (int) $request->get('limit', 10);

            $page = $page > 0 ? $page : 1;
            $limit = $limit > 0 ? $limit : 10;
            $offset = ($page - 1) * $limit;

            $total = $query->count();

            $yardOutwards = $query->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();
            
            $currentPage = floor($offset / $limit) + 1;
            
            return response()->json([
                'success' => true,
                'data' => $yardOutwards,
                'pagination' => [
                    'total' => $total,
                    'page' => $currentPage,
                    'offset' => $offset,
                    'limit' => $limit,
                    'has_more' => ($offset + $limit) < $total,
                    'total_pages' => ceil($total / $limit)
                ],
                'message' => 'Yard outward records retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Yard outward list error', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->all()
            ]);
            
            if (app()->environment('local')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve yard outward records',
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], 500);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve yard outward records',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'gross_weight' => 'required|numeric|min:0',
            'tare_weight' => 'required|numeric|min:0',
            'netweight' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate challan number if present
        // if ($request->has('challane_number') && !empty($request->challane_number)) {
        //     $existingRecord = YardOutward::where('challane_number', $request->challane_number)->first();
        //     if ($existingRecord) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Validation failed',
        //             'errors' => [
        //                 'challane_number' => ['The challan number has already been taken.']
        //             ]
        //         ], 422);
        //     }
        // }

        // Check for duplicate yard_challan_number if present
        // if ($request->has('yard_challan_number') && !empty($request->yard_challan_number)) {
        //     $existingYardChallan = YardOutward::where('yard_challan_number', $request->yard_challan_number)->first();
        //     if ($existingYardChallan) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Validation failed',
        //             'errors' => [
        //                 'yard_challan_number' => ['The yard challan number has already been taken.']
        //             ]
        //         ], 422);
        //     }
        // }

        try {
            $yardOutward = YardOutward::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Yard outward created successfully',
                'data' => $yardOutward
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Yard outward creation error', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create yard outward record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $yardOutward = YardOutward::with([
                'trip', 
                'company:id,company_name', 
                'mine:id,mine_name', 
                'yard:id,yard_name',
                'source:id,name',
                'grade:id,name',
                'size:id,name',
                'modeOfDispatch:id,name',
                'deliveryOrder:id,do_number',
                'yardLocation:id,name',
            ])->find($id);

            if (!$yardOutward) {
                return response()->json([
                    'success' => false,
                    'message' => 'Yard outward record not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $yardOutward
            ]);
            
        } catch (\Exception $e) {
            Log::error('Yard outward show error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve yard outward record',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $yardOutward = YardOutward::find($id);

        if (!$yardOutward) {
            return response()->json([
                'success' => false,
                'message' => 'Yard outward record not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'company_id' => 'sometimes|required|exists:companies,id',
            'mine_id' => 'sometimes|required|exists:mines,id',
            'yard_id' => 'sometimes|required|exists:yards,id',
            'challane_number' => 'sometimes|required|string|max:255',
            'truck_number' => 'sometimes|required|string|max:255',
            'driver_name' => 'sometimes|required|string|max:255',
            'gross_weight' => 'sometimes|required|numeric|min:0',
            'tare_weight' => 'sometimes|required|numeric|min:0',
            'netweight' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate challan number (excluding current record)
        if ($request->has('challane_number') && !empty($request->challane_number)) {
            $existingRecord = YardOutward::where('challane_number', $request->challane_number)
                                       ->where('id', '!=', $id)
                                       ->first();
            if ($existingRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'challane_number' => ['The challan number has already been taken.']
                    ]
                ], 422);
            }
        }

        // Check for duplicate yard_challan_number (excluding current record)
        if ($request->has('yard_challan_number') && !empty($request->yard_challan_number)) {
            $existingYardChallan = YardOutward::where('yard_challan_number', $request->yard_challan_number)
                                            ->where('id', '!=', $id)
                                            ->first();
            if ($existingYardChallan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'yard_challan_number' => ['The yard challan number has already been taken.']
                    ]
                ], 422);
            }
        }

        try {
            $yardOutward->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Yard outward updated successfully',
                'data' => $yardOutward
            ]);
            
        } catch (\Exception $e) {
            Log::error('Yard outward update error', [
                'error' => $e->getMessage(),
                'id' => $id,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update yard outward record',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $yardOutward = YardOutward::findOrFail($id);
            $yardOutward->delete();

            return response()->json([
                'success' => true,
                'message' => 'Yard outward record deleted successfully'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Yard outward record not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Yard outward deletion error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete yard outward record',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Get yard outward records for dropdown (id and challan_number only)
     */
    public function getYardOutwardList(Request $request): JsonResponse
    {
        try {
            $query = YardOutward::select('id', 'challane_number', 'yard_challan_number');
            
            // Search by challan number if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('challane_number', 'like', '%' . $request->search . '%')
                      ->orWhere('yard_challan_number', 'like', '%' . $request->search . '%');
            }
            
            $yardOutwards = $query->orderBy('challane_number', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $yardOutwards
            ]);
            
        } catch (\Exception $e) {
            Log::error('Yard outward list error', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve yard outward list',
                'error' => 'Database error'
            ], 500);
        }
    }

     public function getYardDoNumber(Request $request)
    {
        try {
            
            $query = \App\Models\YardOutward::select(["id", "do_number"])->distinct();
             $query->where('mode_of_dispatch_id', $request->mode_of_dispatch_id);
            // Only get records that have DO numbers
            $query->whereNotNull('do_number')->where('do_number', '!=', '');
            
            $doNumbers = $query->orderBy('do_number', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $doNumbers,
                'message' => 'DO numbers list retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('DO numbers list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve DO numbers list',
                'error' => 'Database error'
            ], 500);
        }
    }


}
