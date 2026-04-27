<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Yard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class YardsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $offset = $request->get('offset', 10);
        
        $query = Yard::with([
                'mode:id,name',
                'company:id,company_name', 
                'mine:id,mine_name'
            ])
            ->where('mode_of_dispatch_id', 1);
            
        // Filter by do_id if provided
        if (!empty($request->get('do_id'))) {
            $query->where('do_id', $request->get('do_id'));
        }
       
        
        $params = $request->input('params', []);

        if (!empty($params['mode_of_dispatch_id'])) {
            $query->where('mode_of_dispatch_id', $params['mode_of_dispatch_id']);
        }

        if (!empty($params['do_id'])) {
            $query->where('id', $params['do_id']);
        }
        
        
        if (!empty($params['do_date'])) {
            $query->whereDate('created_at',$params['do_date']);
        }


        // Filter by do_date if provided
        if (!empty($request->get('do_date'))) {
            $query->whereDate('created_at', $request->get('do_date'));
        }
        
        $yards = $query->paginate($offset, ['*'], 'page', $page)->toArray();
        
        return response()->json([
            'success' => true,
            'data' => $yards["data"]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'gross_weight' => 'required|numeric|min:0',
            'do_number' => 'required|numeric|min:0',
            'do_quantity' => 'required|numeric|min:1',
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

        // Check for duplicate do_number
        if ($request->has('do_number') && !empty($request->do_number)) {
            $existingYard = \App\Models\Yard::where('do_number', $request->do_number)->first();
            if ($existingYard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'do_number' => ['The DO number has already been taken.']
                    ]
                ], 422);
            }
        }

        $yard = Yard::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Yard created successfully',
            'data' => $yard
        ], 201);
    }


    
    /**
     * Store a newly created resource in storage.
     */
    public function saveYardRecord(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'source_id' => 'required',
            'grade_id' => 'required',
            'size_id' => 'required',
            'challane_number' => 'required|string|max:255',
            'truck_number' => 'required|string|max:255',
            'driver_name' => 'required|string|max:255',
            'gross_weight' => 'required|numeric|min:0',
            'tire_weight' => 'required|numeric|min:0',
            'net_weight' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $yard = Yard::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Yard created successfully',
            'data' => $yard
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $yard = Yard::with(['company:id,company_name', 'mine:id,mine_name'])->find($id);

        if (!$yard) {
            return response()->json([
                'success' => false,
                'message' => 'Yard not found'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $yard
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $yard = Yard::find($id);

        if (!$yard) {
            return response()->json([
                'success' => false,
                'message' => 'Yard not found'
            ], 404);
        }

        $validator = \Validator::make($request->all(), [
            'gross_weight' => 'sometimes|numeric|min:0',
            'tare_weight' => 'sometimes|numeric|min:0',
            'netweight' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $yard->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Yard updated successfully',
            'data' => $yard
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $yard = Yard::find($id);

        if (!$yard) {
            return response()->json([
                'success' => false,
                'message' => 'Yard not found'
            ], 404);
        }

        $yard->delete();

        return response()->json([
            'success' => true,
            'message' => 'Yard deleted successfully'
        ]);
    }

    public function getYardDoNumber(Request $request)
    {
        try {
            
            $query = \App\Models\Yard::select(["id", "do_number"])->distinct();
             $query->where('mode_of_dispatch_id', $request->mode_of_dispatch_id);
            // Search by DO number if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('do_number', 'like', '%' . $request->search . '%');
            }
            
            // Filter by status if provided (if yard table has status field)
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }
            
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


    public function getYardRecordByDoORYard(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'do_number' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Initialize data array
            $data = [];
            
            // Build query with OR condition
            $query = \App\Models\Yard::whereNotNull('do_number');
            $query->where('do_number', $request->do_number);
            $result = $query->first();
           
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Total quantity received sum retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Yard record sum error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve total quantity sum',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
