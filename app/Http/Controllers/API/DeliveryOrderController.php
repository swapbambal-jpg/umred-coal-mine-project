<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\DeliveryOrder;
use App\Models\Mine;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DeliveryOrderController extends BaseController
{
    /**
     * Upload base URL constant
     */
    const UPLOAD_BASE_URL = 'https://localhost/umrer_transport/public/uploads/delivery_orders/';
    /**
     * Display a listing of the delivery orders.
     */


    public function getUploadBaseUrl()
    {
        return config('app.url') . 'public/uploads/delivery_orders/';
    }


    public function index(Request $request): JsonResponse
    {
        try {
            $query = DeliveryOrder::with([
                    'size:id,name', 
                    'grade:id,name', 
                    'mine:id,mine_name', 
                    'company:id,company_name']);
            
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

            if (!empty($params['do_number'])) {
                $query->where('do_number', $params['do_number']);
            }

            if (!empty($params['do_date'])) {
                $query->whereDate('created_at', $params['do_date']);
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

                // Apply pagination
                $deliveryOrders = $query->orderBy('created_at', 'desc')
                    ->offset($offset)
                    ->limit($limit)
                    ->get();
            
            // Calculate current page based on offset for response
            $currentPage = floor($offset / $limit) + 1;
            
            return response()->json([
                'success' => true,
                'data' => $deliveryOrders,
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
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve delivery orders',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Store a newly created delivery order in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'do_number' => 'required|string|max:100',
                'total_quantity' => 'required|numeric|min:0',
                'remaining_quantity' => 'required|numeric|min:0',
                'type_of_mode_id' => 'required|integer',
                'file' => 'sometimes|file|max:10240' // Optional file, max 10MB
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            //Validate that remaining quantity doesn't exceed total quantity
            if ($request->remaining_quantity > $request->total_quantity) {
                return $this->sendError('Validation Error.', ['remaining_quantity' => ['Remaining quantity cannot be greater than total quantity.']]);
            }

            // Check for existing record with same type_of_mode_id and do_number
            $existingRecord = DeliveryOrder::where('type_of_mode_id', $request->type_of_mode_id)
                ->where('do_number', $request->do_number)
                ->first();

            if ($existingRecord) {
                return $this->sendError('Validation Error.', ['duplicate' => ['A delivery order with this DO number and type of mode already exists.']]);
            }

            // Handle file upload if present
            $doAttachmentName = null;
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $filename = pathinfo($originalName, PATHINFO_FILENAME);
                
                // Create upload directory if it doesn't exist
                $uploadPath = public_path('uploads/delivery_orders');
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

            $request->merge(['do_actual_qty' => $request->total_quantity]);
            
            // Add attachment name to request data
            if ($doAttachmentName) {
                $request->merge(['do_attachment' => $doAttachmentName]);
            }
            
            $deliveryOrder = DeliveryOrder::create($request->all());
            // Load the relationships for response
            $deliveryOrder->load(['mine:id,mine_name', 'company:id,company_name']);

            return $this->sendResponse($deliveryOrder, 'Delivery order created successfully.');
            
        } catch (\Exception $e) {
            Log::error('Delivery order creation error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to create delivery order.', ['error' => $e->getMessage()]);
        }
    }
    /**
     * Display the specified delivery order.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $deliveryOrder = DeliveryOrder::with([
                        'mine:id,mine_name', 
                        'company:id,company_name', 
                        'trips',
                        'size:id,name',
                        'grade:id,name',
                        'cil_company:id,name',
                        'type_of_purchase:id,name',
                        'type_of_mode:id,name',
                        'type_of_coal:id,name'
                        ])->findOrFail($id);
            
            // Add total trips count to the response
            $deliveryOrder->total_trips = $deliveryOrder->trips()->count();
            //$deliveryOrder->accumulated_qty = $deliveryOrder->trips()->sum('accumulated_qty');

            // Add attachment URL if attachment exists
            if ($deliveryOrder->do_attachment) {
                $deliveryOrder->attachment_url = self::getUploadBaseUrl() . $deliveryOrder->do_attachment;
            } else {
                $deliveryOrder->attachment_url = null;
            }
            
            // Remove trips collection from response to avoid sending all trip data
            unset($deliveryOrder->trips);
            
            return $this->sendResponse($deliveryOrder, 'Delivery order retrieved successfully.');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Delivery order not found.', ['error' => 'Delivery order with ID ' . $id . ' not found']);
            
            
        } catch (\Exception $e) {
            Log::error('Delivery order show error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return $this->sendError('An error occurred while retrieving the delivery order.', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
        }
    }

    /**
     * Update the specified delivery order in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        print_r($request->all());
        die;
        try {
            $deliveryOrder = DeliveryOrder::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'do_number' => 'sometimes|string|max:100',
                'company_id' => 'sometimes|exists:companies,id',
                'mine_id' => 'sometimes|exists:mines,id',
                'total_quantity' => 'sometimes|numeric|min:0',
                'remaining_quantity' => 'sometimes|numeric|min:0',
                'issue_date' => 'sometimes|date',
                'expiry_date' => 'sometimes|date|after_or_equal:issue_date',
                'status' => 'sometimes|in:active,expired,completed,cancelled',
                'do_area' => 'sometimes|string|max:255',
                'type_of_mode_id' => 'sometimes|integer',
                'file' => 'sometimes|file|max:10240' // Optional file, max 10MB
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            // Validate quantity relationship if both are provided
            $totalQuantity = $request->total_quantity ?? $deliveryOrder->total_quantity;
            $remainingQuantity = $request->remaining_quantity ?? $deliveryOrder->remaining_quantity;
            
            if ($remainingQuantity > $totalQuantity) {
                return $this->sendError('Validation Error.', ['remaining_quantity' => ['Remaining quantity cannot be greater than total quantity.']]);
            }

            // Check for duplicate record with same type_of_mode_id and do_number (excluding current record)
            $doNumber = $request->do_number ?? $deliveryOrder->do_number;
            $typeOfModeId = $request->type_of_mode_id ?? $deliveryOrder->type_of_mode_id;

            if ($request->has('do_number') || $request->has('type_of_mode_id')) {
                $existingRecord = DeliveryOrder::where('type_of_mode_id', $typeOfModeId)
                    ->where('do_number', $doNumber)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingRecord) {
                    return $this->sendError('Validation Error.', ['duplicate' => ['Another delivery order with this DO number and type of mode already exists.']]);
                }
            }
            
            // Handle file upload if present
            
            $doAttachmentName = null;
            if ($request->hasFile('file') && $request->file('file')->isValid()) {

                
                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $filename = pathinfo($originalName, PATHINFO_FILENAME);
                
                // Create upload directory if it doesn't exist
                $uploadPath = public_path('uploads/delivery_orders');
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
                if ($deliveryOrder->do_attachment) {
                    $oldFilePath = $uploadPath . '/' . $deliveryOrder->do_attachment;
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
            }
        
            // Add attachment name to request data
            if ($doAttachmentName) {
                $request->merge(['do_attachment' => $doAttachmentName]);
            }

            $deliveryOrder->update($request->all());

            // Load the relationships for response
            $deliveryOrder->load(['mine:id,mine_name', 'company:id,company_name']);

            return $this->sendResponse($deliveryOrder, 'Delivery order updated successfully.');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Delivery order not found.', ['error' => 'Delivery order with ID ' . $id . ' not found']);
            
        } catch (\Exception $e) {
            Log::error('Delivery order update error', [
                'error' => $e->getMessage(),
                'id' => $id,
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to update delivery order.', ['error' => 'Database error']);
        }
    }

    /**
     * Remove the specified delivery order from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $deliveryOrder = DeliveryOrder::findOrFail($id);
            
            // Delete related trips first
            $deletedTrips = \App\Models\Trip::where('do_id', $id)->delete();
            $deletedTrips = \App\Models\Plant::where('do_number', $deliveryOrder->do_number)->delete();
            
            // Delete the delivery order
            $deliveryOrder->delete();

            return $this->sendResponse([
                'deleted_delivery_order' => true,
                'deleted_related_trips' => $deletedTrips
            ], 'Delivery order and related trips deleted successfully.');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Delivery order not found.', ['error' => 'Delivery order with ID ' . $id . ' not found']);
            
        } catch (\Exception $e) {
            Log::error('Delivery order deletion error', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            return $this->sendError('Failed to delete delivery order.', ['error' => 'Database error']);
        }
    }

    /**
     * Get delivery orders list for dropdown (id and do_number only)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getDeliveryOrderList(Request $request)
    {
        try {
            $query = DeliveryOrder::select('id', 'do_number');
            
            // Search by DO number if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('do_number', 'like', '%' . $request->search . '%');
            }
            
            // By default, only show active delivery orders
            if (!$request->has('status')) {
                $query->where('status', 'active');
            } else if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }
            
            $deliveryOrders = $query->orderBy('do_number', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $deliveryOrders,
                'message' => 'Delivery order list retrieved successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Delivery order dropdown list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve delivery order list',
                'error' => 'Database error'
            ], 500);
        }
    }

    /**
     * Get delivery orders by mine_id
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getDeliveryOrdersByMine(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mine_id' => 'required|exists:mines,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        try {
            $query = DeliveryOrder::select('id', 'do_number')
                ->where('mine_id', $request->mine_id);
            
            // Filter by status if provided
            if ($request->has('status') && in_array($request->status, ['active', 'expired', 'completed', 'cancelled'])) {
                $query->where('status', $request->status);
            }
            
            // Search by DO number if provided
            if ($request->has('search') && !empty($request->search)) {
                $query->where('do_number', 'like', '%' . $request->search . '%');
            }
            
            $deliveryOrders = $query->orderBy('do_number', 'asc')->get();
            
            return $this->sendResponse($deliveryOrders, 'Delivery orders retrieved successfully for mine ID: ' . $request->mine_id);
            
        } catch (\Exception $e) {
            Log::error('Delivery orders by mine error', [
                'error' => $e->getMessage(),
                'mine_id' => $request->mine_id,
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to retrieve delivery orders.', ['error' => 'Database error']);
        }
    }

    /**
     * Get delivery orders list for dropdown by type_of_mode_id
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getDoNumberList(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type_of_mode_id' => 'required|integer'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $query = DeliveryOrder::select(['id', 'do_number as name'])
                ->where('type_of_mode_id', $request->type_of_mode_id);
            $deliveryOrders = $query->orderBy('id', 'desc')->get();
            
            return $this->sendResponse($deliveryOrders, 'Delivery order numbers retrieved successfully for type_of_mode_id: ' . $request->type_of_mode_id);
            
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Delivery order numbers list database error', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'request' => $request->all()
            ]);
            
            return $this->sendError('Database error occurred.', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Delivery order numbers list error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to retrieve delivery order numbers.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update the status of a delivery order by ID
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        // Log incoming request for debugging
        Log::info('Delivery order status update request', [
            'id' => $id,
            'request_data' => $request->all(),
            'method' => $request->method()
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:active,inactive'
            ], [
                'status.required' => 'Status field is required',
                'status.in' => 'Status must be one of: active, inactive'
            ]);

            if ($validator->fails()) {
                Log::warning('Delivery order status validation failed', [
                    'errors' => $validator->errors(),
                    'request' => $request->all()
                ]);
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $deliveryOrder = DeliveryOrder::find($id);
            
            if (!$deliveryOrder) {
                Log::warning('Delivery order not found', ['id' => $id]);
                return $this->sendError('Delivery order not found.', ['error' => 'Delivery order with ID ' . $id . ' not found']);
            }
            
            // Log before update
            Log::info('Updating delivery order status', [
                'id' => $id,
                'old_status' => $deliveryOrder->status,
                'new_status' => $request->status
            ]);
            
            // Update the status
            $deliveryOrder->status = $request->status;
            $deliveryOrder->save();

            // Load the relationships for response
            $deliveryOrder->load(['mine:id,mine_name', 'company:id,company_name']);

            Log::info('Delivery order status updated successfully', [
                'id' => $id,
                'new_status' => $deliveryOrder->status
            ]);

            return $this->sendResponse($deliveryOrder, 'Delivery order status updated successfully.');
            
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Delivery order status update database error', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'id' => $id,
                'request' => $request->all()
            ]);
            
            return $this->sendError('Database error occurred.', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Delivery order status update error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
                'request' => $request->all()
            ]);
            
            return $this->sendError('Failed to update delivery order status.', ['error' => $e->getMessage()]);
        }
    }
    
/**
     * Delete trip attachment image
     */
    public function deleteTripAttachment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'trip_id' => 'required|exists:delivery_orders,id',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $trip = DeliveryOrder::find($request->trip_id);

            if (!$trip) {
                return $this->sendError('Trip not found.', ['error' => 'Trip not found']);
            }

            // Check if trip has an attachment
            if (!$trip->do_attachment) {
                return $this->sendError('No attachment found.', ['error' => 'This trip has no attachment to delete']);
            }

            // Delete the file from uploads folder
            $filePath = public_path('uploads/delivery_orders/' . $trip->do_attachment);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Update the database record to remove attachment reference
            $trip->do_attachment = null;
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

}
