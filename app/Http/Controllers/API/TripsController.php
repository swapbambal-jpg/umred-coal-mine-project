<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Trip;
use App\Models\LogTrip;
use App\Models\Plant;
use App\Models\DeliveryOrder;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TripsController extends BaseController
{
    

    const UPLOAD_BASE_URL = 'https://localhost/umrer_transport/public/uploads/trips/';
    /**
     * Display a listing of the trips.
     */
      public function index(Request $request): JsonResponse
    {
        
        try {
              $query = Trip::with([
                'deliveryOrder:id,do_number,total_quantity,party_name,do_actual_qty',
                'company:id,company_name',
                'type_of_wagon:id,name'
            ]);
            
            // Filter by status if provided
            if ($request->has('status') && in_array($request->status, ['active', 'expired', 'completed', 'cancelled'])) {
                $query->where('status', $request->status);
            }
            
            // Filter by company_id if provided
            if ($request->has('company_id') && !empty($request->company_id)) {
                $query->where('company_id', $request->company_id);
            }

            $params = $request->input('params', []);

            if (!empty($params['type_of_mode_id'])) {
                $query->where('type_of_mode_id', $params['type_of_mode_id']);
            }

            if (!empty($params['do_id'])) {
                $query->where('do_id', $params['do_id']);
            }

            if (!empty($params['trip_id'])) {
                $query->where('id', $params['trip_id']);
            }
            
            
            if (!empty($params['do_date'])) {
                $query->whereRaw('DATE(COALESCE(trip_date, created_at)) = ?', [$params['do_date']]);
            }
            
            // Filter by mine_id if provided
            if ($request->has('mine_id') && !empty($request->mine_id)) {
                $query->where('mine_id', $request->mine_id);
            }
            
            // Search by DO number if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('do_number', 'like', '%' . $request->search . '%');
            }
            
            // Filter by date range if provided
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('issue_date', '>=', $request->date_from);
            }
            
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('issue_date', '<=', $request->date_to);
            }
            
            // Pagination parameters - handle both query params and JSON body
            $requestData = $request->all();
                            // Get page & limit properly
                $page  = isset($requestData['params']['page']) 
                            ? (int) $requestData['params']['page'] 
                            : (int) $request->get('page', 1);

                $limit = isset($requestData['params']['limit']) 
                            ? (int) $requestData['params']['limit'] 
                            : (int) $request->get('limit', 10);

                // Ensure valid values
                $page  = $page > 0 ? $page : 1;
                $limit = $limit > 0 ? $limit : 10;

                // Calculate offset
                $offset = ($page - 1) * $limit;

                // Debug (optional)
                // print_r($offset); exit;

                // Get total count
                $total = $query->count();

                // Enable query logging and clear any previous queries
                // DB::enableQueryLog();
                // DB::flushQueryLog();
                
                // Apply pagination
                $deliveryOrders = $query->orderBy('id', 'desc')
                    ->offset($offset)
                    ->limit($limit)
                    ->get();
                
                // Get and display the first query (main trips table query)
                $queries = DB::getQueryLog();
                $firstQuery = reset($queries);
                
                // echo "<pre>";
                // echo "Main Trips Table Query: " . $firstQuery['query'] . "\n";
                // echo "Bindings: " . json_encode($firstQuery['bindings']) . "\n";
                // echo "Execution Time: " . $firstQuery['time'] . " ms\n";
                // echo "</pre>";
                // exit;
            
            // Calculate current page based on offset for response
            $currentPage = floor($offset / $limit) + 1;
            
            return response()->json([
                'success' => true,
                'data' => $deliveryOrders,
                "UPLOAD_BASE_URL" =>self::getUploadBaseUrl(),
                'pagination' => [
                    'total' => $total,
                    'page' => $currentPage,
                    'offset' => $offset,
                    'limit' => $limit,
                    'has_more' => ($offset + $limit) < $total,
                    'total_pages' => ceil($total / $limit)
                ],
                'message' => 'Delivery orders retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Delivery order list error', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->all()
            ]);
            
            // Return detailed error in development for debugging
            if (app()->environment('local')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve delivery orders',
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], 500);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve delivery orders',
                'error' => 'Database error'
            ], 500);
        }
    }

    public function calculateTripsweight(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'do_id' => 'nullable|exists:delivery_orders,id',
         ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $type_of_mode_id = !empty($request->type_of_mode_id) ? $request->type_of_mode_id : 1;
        $this->recalculateTrips($request->do_id,$type_of_mode_id);
        return $this->sendResponse([], 'Trips weight calculated successfully');

    }

    /**
     * Store a newly created trip in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'do_id' => 'nullable|exists:delivery_orders,id',
            'company_id' => 'nullable|exists:companies,id',
            'operator_id' => 'nullable|exists:users,id',
            'driver_name' => 'nullable|string|max:30',
            'truck_number' => 'nullable|string|max:255',
            'tare_weight' => 'nullable|numeric|min:0',
            'gross_weight' => 'nullable|numeric|min:0',
            'netweight' => 'nullable|numeric|min:0',
            'lifted_quantity' => 'nullable|numeric|min:0',
            'remaining_quantity' => 'nullable|numeric|min:0',
            'entry_status' => 'nullable|numeric',
            'trip_date' => 'nullable|date'
        ]);

        

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
 

        try {
            DB::beginTransaction();

            $deliveryOrder = \App\Models\DeliveryOrder::where('id', $request->do_id)
                                            ->select('id', 'do_actual_qty')
                                            ->firstOrFail();
            // 🚨 Prevent over dispatch (before insert)
            $totalDispatched = \App\Models\Trip::where('do_id', $request->do_id)
                ->sum('netweight');


            if (($totalDispatched + $request->netweight) > $deliveryOrder->do_actual_qty) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dispatch quantity exceeds DO quantity'
                ], 400);
            }

               // Handle file upload if present
            $doAttachmentName = null;
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $filename = pathinfo($originalName, PATHINFO_FILENAME);
                
                // Create upload directory if it doesn't exist
                $uploadPath = public_path('uploads/trips');
                if (!file_exists($uploadPath)) {
                    if (!mkdir($uploadPath, 0755, true)) {
                        return $this->sendError('Failed to create upload directory.', ['error' => 'Unable to create directory: ' . $uploadPath]);
                    }
                }
                
                // Generate unique filename to avoid duplicates
                $counter = 1;
                $timestamp = date('YmdHis');
                $newFilename = $timestamp . '_' . $originalName;
                while (file_exists($uploadPath . '/' . $newFilename)) {
                    $newFilename = $timestamp . '_' . $filename . '_' . $counter . '.' . $extension;
                    $counter++;
                }
                
                // Move the file
                if (!$file->move($uploadPath, $newFilename)) {
                    return $this->sendError('Failed to upload file.', ['error' => 'Unable to move file to upload directory']);
                }
                $doAttachmentName = $newFilename;
            }
            
            // Handle base64 image upload
            if ($request->has('mobile_image') && !empty($request->mobile_image)) {
                $base64Image = $request->mobile_image;
                
                // Validate base64 format
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
                    $imageType = $matches[1];
                    $base64Data = substr($base64Image, strpos($base64Image, ',') + 1);
                    $binaryData = base64_decode($base64Data);
                    
                    if ($binaryData === false) {
                        return $this->sendError('Invalid base64 image data.', ['error' => 'Failed to decode base64 image']);
                    }
                    
                    // Create upload directory if it doesn't exist
                    $uploadPath = public_path('uploads/trips');
                    if (!file_exists($uploadPath)) {
                        if (!mkdir($uploadPath, 0755, true)) {
                            return $this->sendError('Failed to create upload directory.', ['error' => 'Unable to create directory: ' . $uploadPath]);
                        }
                    }
                    
                    // Generate unique filename
                    $timestamp = date('YmdHis');
                    $newFilename = $timestamp . '_mobile_image.' . $imageType;
                    
                    // Save the image
                    if (file_put_contents($uploadPath . '/' . $newFilename, $binaryData) === false) {
                        return $this->sendError('Failed to save base64 image.', ['error' => 'Unable to save image file']);
                    }
                    
                    $doAttachmentName = $newFilename;
                    $request->merge(['mobile_image_status' => 1]);
                } else {
                    return $this->sendError('Invalid base64 image format.', ['error' => 'Base64 image must start with data:image/']);
                }
            }

            
            // Add attachment name to request data
            if ($doAttachmentName) {
                $request->merge(['trip_attachment' => $doAttachmentName]);
            }

            // Check for duplicate record
            $existingTrip = \App\Models\Trip::where('do_id', $request->do_id)
                ->where('type_of_mode_id', $request->type_of_mode_id)
                ->where('delivery_challan_number', $request->delivery_challan_number)
                ->first();

            if ($existingTrip) {
                return $this->sendError('Duplicate Entry.', ['This dispatch challan number is already exists.']);
            }

            // ✅ Create Trip
            $trip = \App\Models\Trip::create($request->all());

            // ✅ Recalculate everything
            $this->recalculateTrips($request->do_id,$request->type_of_mode_id);

            DB::commit();

            // Load relationships for response
            $trip->load([
                'deliveryOrder:id,do_number',
                'company:id,company_name',
                'operator:id,name'
            ]);
            
            return $this->sendResponse($trip, 'Trip created successfully.');
            
        } catch (\Exception $e) {
            
            Log::error('Trip creation error', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->all()
            ]);
            
            // Return more detailed error in development
            if (app()->environment('local')) {
                return $this->sendError('Failed to create trip.', [
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return $this->sendError('Failed to create trip.', ['error' => 'Database error']);
        }
    }


    private function recalculateTrips($doId,$type_of_mode_id=1){


        try {
            $deliveryOrder = \App\Models\DeliveryOrder::where('id', $doId)
                ->where('type_of_mode_id', $type_of_mode_id)
                ->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Do is not found', ['Do is not found']);
        }


        $trips = \App\Models\Trip::where('do_id', $doId)->where('type_of_mode_id', $type_of_mode_id)->orderBy('id',"asc")->get();
        $do_actual_qty = $deliveryOrder->do_actual_qty;
        $tripCounter = 1;
        $progressive = 0;

        foreach ($trips as $trip) {

            $total_netweight =($trip->gross_weight-$trip->tare_weight);
            $do_actual_qty =($do_actual_qty-$total_netweight);
            $progressive = $progressive + $total_netweight;
            $trip->update([
                'accumulated_qty' => $progressive,
                'total_trips' => $tripCounter,
                'total_quantity' => $deliveryOrder->do_actual_qty,
                'remaining_quantity' => $do_actual_qty,
                'netweight' =>$total_netweight,
            ]);
            $tripCounter++;
        }
        $data = \App\Models\Trip::where('do_id',$doId)->where('type_of_mode_id', $type_of_mode_id)->selectRaw('COUNT(*) as total_trips, SUM(netweight) as total_netweight')->first();
        $doRemainingQty = $deliveryOrder->do_actual_qty - $data->total_netweight;
        //✅Update main table
        $deliveryOrder->update([
            'accumulated_qty' => $progressive,
            'remaining_quantity' => $doRemainingQty,
            'total_quantity' => $deliveryOrder->do_actual_qty,
            'total_trips' => $data->total_trips
        ]);
        
    }

    /**
     * Display the specified trip.
     */
    public function show($id): JsonResponse
    {
        try {
            
            $trip = Trip::with([
                'deliveryOrder:id,do_number',
                'company:id,company_name',
                'type_of_mode:id,name',
                'mine:id,mine_name'
            ])->find($id);
            
            if (!$trip) {
                return $this->sendError('Trip not found.', ['error' => 'Trip not found']);
            }

            if ($trip->trip_attachment) {
                $trip->trip_attachment = self::getUploadBaseUrl() . $trip->trip_attachment;
            } else {
                $trip->attachment_url = null;
            }
            
            return $this->sendResponse($trip, 'Trip retrieved successfully.');
            
        } catch (\Exception $e) {
            Log::error('Trip show error', [
                'error' => $e->getMessage(),
                'trip_id' => $id
            ]);
            
            // Return detailed error in development
            if (app()->environment('local')) {
                return $this->sendError('Failed to retrieve trip.', [
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return $this->sendError('Failed to retrieve trip.', ['error' => 'Database error']);
        }
    }

    /**
     * Update the specified trip in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {

          // print_r($request->all());exit;
        $validator = Validator::make($request->all(), [
            'do_id' => 'sometimes|required|exists:delivery_orders,id',
            'company_id' => 'sometimes|required|exists:companies,id',
            'operator_id' => 'sometimes|required|exists:users,id',
            'driver_name' => 'nullable|string|max:30',
            'truck_number' => 'nullable|string|max:255',
            'tare_weight' => 'nullable|numeric|min:0',
            'gross_weight' => 'nullable|numeric|min:0',
            'netweight' => 'nullable|numeric|min:0',
            'lifted_quantity' => 'nullable|numeric|min:0',
            'remaining_quantity' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        try {
            $tripRecord = Trip::find($id);
            
            if (!$tripRecord) {
                return $this->sendError('Trip not found.', ['error' => 'Trip not found']);
            } 
            
            // Handle file upload if present
            
            $doAttachmentName = null;
            if ($request->hasFile('file') && $request->file('file')->isValid()) {

                
                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $filename = pathinfo($originalName, PATHINFO_FILENAME);
                
                // Create upload directory if it doesn't exist
                $uploadPath = public_path('uploads/trips');
                if (!file_exists($uploadPath)) {
                    if (!mkdir($uploadPath, 0755, true)) {
                        return $this->sendError('Failed to create upload directory.', ['error' => 'Unable to create directory: ' . $uploadPath]);
                    }
                }
                
                // Generate unique filename to avoid duplicates
                $counter = 1;
                $timestamp = time();
                $newFilename = $timestamp . '_' . $originalName;
                while (file_exists($uploadPath . '/' . $newFilename)) {
                    $newFilename = $timestamp . '_' . $filename . '_' . $counter . '.' . $extension;
                    $counter++;
                }
                
                // Move the file
                if (!$file->move($uploadPath, $newFilename)) {
                    return $this->sendError('Failed to upload file.', ['error' => 'Unable to move file to upload directory']);
                }
                $doAttachmentName = $newFilename;
                
                // Delete old file if exists
                if ($tripRecord->trip_attachment) {
                    $oldFilePath = $uploadPath . '/' . $tripRecord->trip_attachment;
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
            }
            
            // Handle base64 image upload
            if ($request->has('mobile_image') && !empty($request->mobile_image)) {
                $base64Image = $request->mobile_image;
                
                // Validate base64 format
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
                    $imageType = $matches[1];
                    $base64Data = substr($base64Image, strpos($base64Image, ',') + 1);
                    $binaryData = base64_decode($base64Data);
                    
                    if ($binaryData === false) {
                        return $this->sendError('Invalid base64 image data.', ['error' => 'Failed to decode base64 image']);
                    }
                    
                    // Create upload directory if it doesn't exist
                    $uploadPath = public_path('uploads/trips');
                    if (!file_exists($uploadPath)) {
                        if (!mkdir($uploadPath, 0755, true)) {
                            return $this->sendError('Failed to create upload directory.', ['error' => 'Unable to create directory: ' . $uploadPath]);
                        }
                    }
                    
                    // Generate unique filename
                    $timestamp = date('YmdHis');
                    $newFilename = $timestamp . '_mobile_image.' . $imageType;
                    
                    // Save the image
                    if (file_put_contents($uploadPath . '/' . $newFilename, $binaryData) === false) {
                        return $this->sendError('Failed to save base64 image.', ['error' => 'Unable to save image file']);
                    }
                    
                    $doAttachmentName = $newFilename;
                    $request->merge(['mobile_image_status' => 1]);
                    
                    // Delete old file if exists
                    if ($tripRecord->trip_attachment) {
                        $oldFilePath = $uploadPath . '/' . $tripRecord->trip_attachment;
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                } else {
                    return $this->sendError('Invalid base64 image format.', ['error' => 'Base64 image must start with data:image/']);
                }
            }
        
            // Add attachment name to request data
            if ($doAttachmentName) {
                $request->merge(['trip_attachment' => $doAttachmentName]);
            }

            unset($request['accumulated_qty']);
            if($tripRecord->update($request->all())){

                // Recalculate progressive for all trips of this DO
                $trips = Trip::where('do_id', $tripRecord->do_id)->orderBy('id')->get();

                $runningTotal = 0;
                $tripCounter = 1;

                foreach ($trips as $t) {
                    $runningTotal += $t->netweight;
                    $t->accumulated_qty = $runningTotal;
                    $t->total_trips = $tripCounter;
                    $t->save();
                    $tripCounter++;
                }

                $devileryOrderRecord = \App\Models\DeliveryOrder::where('id', $request->do_id)->first();
               // print_r($request->all());exit;
                $updateDoData['accumulated_qty'] =$runningTotal;
                if (!empty($updateDoData)) {
                    $devileryOrderRecord->update($updateDoData);
                }

            }
            
            // Load relationships for response
            $tripRecord->load([
                'deliveryOrder:id,do_number',
                'company:id,company_name',
                'operator:id,name'
            ]);
            
            return $this->sendResponse($tripRecord, 'Trip updated successfully.');
            
        } catch (\Exception $e) {
            Log::error('Trip update error', [
                'error' => $e->getMessage(),
                'trip_id' => $id,
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to update trip.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified trip from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            
            // First try to find trip using raw SQL
            $trip = DB::select('SELECT * FROM trips WHERE id = ?', [$id]);
            
            if (empty($trip)) {
                return $this->sendError('Trip not found.', ['error' => 'Trip not found']);
            }
            
            $tripData = $trip[0];
            // Start database transaction
            DB::beginTransaction();
            
            try {
                // Log trip record before deletion
                $logTrip = new LogTrip();
                $logTrip->original_trip_id = $tripData->id;
                $logTrip->do_id = $tripData->do_id;
                $logTrip->type_of_mode_id = $tripData->type_of_mode_id;
                $logTrip->company_id = $tripData->company_id;
                $logTrip->mine_id = $tripData->mine_id;
                $logTrip->operator_id = $tripData->operator_id;
                $logTrip->driver_name = $tripData->driver_name;
                $logTrip->truck_number = $tripData->truck_number;
                $logTrip->tare_weight = $tripData->tare_weight;
                $logTrip->gross_weight = $tripData->gross_weight;
                $logTrip->netweight = $tripData->netweight;
                $logTrip->lifted_quantity = $tripData->lifted_quantity;
                $logTrip->remaining_quantity = $tripData->remaining_quantity;
                $logTrip->trip_date = $tripData->trip_date;
                $logTrip->entry_status = $tripData->entry_status;
                $logTrip->total_trips = $tripData->total_trips;
                $logTrip->accumulated_qty = $tripData->accumulated_qty;
                $logTrip->truck_owner_name = $tripData->truck_owner_name;
                $logTrip->delivery_challan_number = $tripData->delivery_challan_number;
                $logTrip->cil_subsidiary = $tripData->cil_subsidiary;
                $logTrip->type_of_coal = $tripData->type_of_coal;
                $logTrip->grad_name = $tripData->grad_name;
                $logTrip->size_name = $tripData->size_name;
                $logTrip->destination_name = $tripData->destination_name;
                $logTrip->rr_weight = $tripData->rr_weight;
                $logTrip->fnr_number = $tripData->fnr_number;
                $logTrip->chargeble_weight = $tripData->chargeble_weight;
                $logTrip->difference = $tripData->difference;
                $logTrip->over_load = $tripData->over_load;
                $logTrip->penalty = $tripData->penalty;
                $logTrip->no_of_wagons = $tripData->no_of_wagons;
                $logTrip->loaded_wagons = $tripData->loaded_wagons;
                $logTrip->total_loaded = $tripData->total_loaded;
                $logTrip->total_balance = $tripData->total_balance;
                $logTrip->stick_wagons = $tripData->stick_wagons;
                $logTrip->type_of_wagons = $tripData->type_of_wagons;
                $logTrip->deleted_by = auth()->user()->name ?? 'System';
                $logTrip->delete_reason = 'Trip deleted from system';
                $logTrip->deleted_at = now();
                $logTrip->save();
                

                 // Delete the trip first
                $deleted = DB::delete('DELETE FROM trips WHERE id = ?', [$id]);

                // Delete plant records with the same do_id
                if (!empty($tripData->do_id)) {
                    DB::delete('DELETE FROM plants WHERE do_id = ?', [$tripData->do_id]);
                }

                // Recalculate trips after deletion
                $this->recalculateTrips($tripData->do_id,$tripData->type_of_mode_id);
                
                
                // Commit the transaction
                DB::commit();
                
                return $this->sendResponse([], 'Trip deleted successfully, logged to log_trips, delivery order quantities updated, and related plant records deleted.');
                
            } catch (\Exception $e) {
                // Rollback if any error occurs
                DB::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Trip deletion error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return $this->sendError('Failed to delete trip.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get trips list for dropdown (id and basic info only)
     */
    public function getTripsList(Request $request): JsonResponse
    {
        try {
            $query = Trip::select('id', 'driver_name', 'truck_number', 'trip_date');
            
            // Filter by company_id if provided
            if ($request->has('company_id') && !empty($request->company_id)) {
                $query->where('company_id', $request->company_id);
            }
            
            // Search by driver name or truck number if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function($q) use ($request) {
                    $q->where('driver_name', 'like', '%' . $request->search . '%')
                      ->orWhere('truck_number', 'like', '%' . $request->search . '%');
                });
            }
            
            $trips = $query->orderBy('trip_date', 'desc')->get();
            
            return $this->sendResponse($trips, 'Trips list retrieved successfully.');
            
        } catch (\Exception $e) {
            Log::error('Trips list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to retrieve trips list.', ['error' => 'Database error']);
        }
    }

    public function getUploadBaseUrl()
    {
        return config('app.url') . 'public/uploads/trips/';
    }

    /**
     * Delete trip attachment image
     */
    public function deleteTripAttachment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'trip_id' => 'required|exists:trips,id',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $trip = Trip::find($request->trip_id);

            if (!$trip) {
                return $this->sendError('Trip not found.', ['error' => 'Trip not found']);
            }

            // Check if trip has an attachment
            if (!$trip->trip_attachment) {
                return $this->sendError('No attachment found.', ['error' => 'This trip has no attachment to delete']);
            }

            // Delete the file from uploads folder
            $filePath = public_path('uploads/trips/' . $trip->trip_attachment);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Update the database record to remove attachment reference
            $trip->trip_attachment = null;
            $trip->save();

            return $this->sendResponse([], 'Trip attachment deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Trip attachment deletion error', [
                'error' => $e->getMessage(),
                'trip_id' => $request->trip_id
            ]);

            return $this->sendError('Failed to delete attachment.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get delivery challan list for dropdown (id and delivery_challan_number only)
     */
    public function getTripChallanList(Request $request): JsonResponse {

        
        try {
            $query = null;
            
            // Get type_of_mode_id from request (supports both GET and POST)
            $type_of_mode_id = $request->input('type_of_mode_id');
            $do_id = $request->input('do_id');
            
            switch($type_of_mode_id){
                case 1:
                case 3:
                    $query = Trip::select('id', 'delivery_challan_number as name')
                        ->whereNotNull('delivery_challan_number')
                        ->where('delivery_challan_number', '!=', '')
                        ->where('type_of_mode_id', $type_of_mode_id);
                    break;
                case 2:
                case 4:
                    $query = Trip::select('id', 'rr_number as name')
                        ->whereNotNull('rr_number')
                        ->where('rr_number', '!=', '')
                        ->where('type_of_mode_id', $type_of_mode_id);
                    break;
                default:
                    return $this->sendError('Invalid type_of_mode_id', ['error' => 'Invalid type_of_mode_id']);
            }

            // Add do_id filter if present
            if ($do_id) {
                $query->where('do_id', $do_id);
            }

            $challans = $query->orderBy('name', 'asc')->get();
            
            return $this->sendResponse($challans, 'Delivery challan list retrieved successfully.');
            
        } catch (\Exception $e) {
            Log::error('Delivery challan list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to retrieve delivery challan list.', ['error' => 'Database error']);
        }
        
    }

    /**
     * Get delivery challan numbers by do_id
     */
    public function getDeliveryChallanByDoId(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'do_id' => 'required|exists:delivery_orders,id',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $challans = Trip::select('id', 'delivery_challan_number')
                ->where('do_id', $request->do_id)
                ->whereNotNull('delivery_challan_number')
                ->where('delivery_challan_number', '!=', '')
                ->orderBy('delivery_challan_number', 'asc')
                ->get();

            return $this->sendResponse($challans, 'Delivery challan numbers retrieved successfully.');
            
        } catch (\Exception $e) {
            Log::error('Delivery challan by do_id error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to retrieve delivery challan numbers.', ['error' => 'Database error']);
        }
    }

    /**
     * Display the specified trip.
     */
    public function getTripsRecordByChallanNumber($id): JsonResponse
    {
        try {
            $trip = Trip::with([
                'deliveryOrder:id,do_number,do_actual_qty,delivery_challan_number,delivery_challan_number,destination_name',
                'company:id,company_name',
                'mine:id,mine_name',
                'operator:id,name'
            ])->find($id);
            
            if (!$trip) {
                return $this->sendError('Trip not found.', ['error' => 'Trip not found']);
            }

            $total_netweight = Plant::whereDeliveryChallanNumber($id)->sum('netweight');
            //$totalActual = $trip->deliveryOrder->do_actual_qty;



            $trip->total_quantity_received = $total_netweight;
            $trip->netweight =($trip->netweight-$total_netweight);


            $totalTrips = Plant::whereDeliveryChallanNumber($id)->count();

            $trip->total_trips = $totalTrips;
            
            return $this->sendResponse($trip, 'Trip retrieved successfully.');
            
        } catch (\Exception $e) {
            Log::error('Trip show error', [
                'error' => $e->getMessage(),
                'trip_id' => $id
            ]);
            
            // Return detailed error in development for debugging
            if (app()->environment('local')) {
                return $this->sendError('Failed to retrieve trip.', [
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return $this->sendError('Failed to retrieve trip.', ['error' => 'Database error']);
        }
    }


  public function downloadAttachment($id)
{
    $trip = Trip::findOrFail($id);

    if (!$trip->trip_attachment) {
        abort(404, 'Attachment not found.');
    }

    $fullPath = public_path($trip->trip_attachment);

    if (!file_exists($fullPath)) {
        abort(404, 'File does not exist.');
    }

    return response()->download($fullPath, basename($fullPath));
}

    

    
}
