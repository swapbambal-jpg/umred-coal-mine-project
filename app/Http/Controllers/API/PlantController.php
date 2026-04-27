<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plant;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PlantController extends Controller
{

     const UPLOAD_BASE_URL = 'https://localhost/umrer_transport/public/uploads/plants/';
    /**
     * Display a listing of the resource.
     */


    public function getUploadBaseUrl()
    {
        return config('app.url') . 'public/uploads/plants/';
    }
    
    public function index(Request $request): JsonResponse
    {
        try {
            
            $query = Plant::with([
                'trip', 
                'company:id,company_name', 
                'mine:id,mine_name', 
                'do:id,do_number,party_name',
                'type_of_mode:id,name'
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
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'type_of_mode_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for unique plant_challan_number if present
        if ($request->has('plant_challan_number') && !empty($request->plant_challan_number)) {
            $existingPlantChallan = Plant::where('plant_challan_number', $request->plant_challan_number)->first();
            if ($existingPlantChallan) {
                return response()->json([
                    'success' => false,
                    'message' => 'The plant challan number has already been taken.',
                    'errors' => [
                        'plant_challan_number' => []
                    ]
                ], 422);
            }
        }

        // Check for unique rr_number if present (in case field exists in future)
        if ($request->has('rr_number') && !empty($request->rr_number)) {
            $existingRR = Plant::where('rr_number', $request->rr_number)->first();
            if ($existingRR) {
                return response()->json([
                    'success' => false,
                    'message' => 'The RR number has already been taken.',
                    'errors' => [
                        'rr_number' => ['The RR number has already been taken.']
                    ]
                ], 422);
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
                $uploadPath = public_path('uploads/plants');
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
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid base64 image data',
                            'error' => 'Failed to decode base64 image'
                        ], 422);
                    }
                    
                    // Create upload directory if it doesn't exist
                    $uploadPath = public_path('uploads/plants');
                    if (!file_exists($uploadPath)) {
                        if (!mkdir($uploadPath, 0755, true)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Failed to create upload directory',
                                'error' => 'Unable to create directory: ' . $uploadPath
                            ], 500);
                        }
                    }
                    
                    // Generate unique filename
                    $timestamp = date('YmdHis');
                    $newFilename = $timestamp . '_mobile_image.' . $imageType;
                    
                    // Save the image
                    if (file_put_contents($uploadPath . '/' . $newFilename, $binaryData) === false) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to save base64 image',
                            'error' => 'Unable to save image file'
                        ], 500);
                    }
                    
                    $doAttachmentName = $newFilename;
                    $request->merge(['mobile_image_status' => 1]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid base64 image format',
                        'error' => 'Base64 image must start with data:image/'
                    ], 422);
                }
            }

            
            // Add attachment name to request data
            if ($doAttachmentName) {
                $request->merge(['plant_attachment' => $doAttachmentName]);
            }

        $plant = Plant::create($request->all());

        // Update remaining_quantity in trips record by do_number
        // if ($plant && $request->has('delivery_challan_number') && $request->has('netweight')) {
        //     $trip = \App\Models\Trip::where('id', $request->all()['delivery_challan_number'])
        //         ->first();

        //     if ($trip) {
        //         $newRemainingQuantity = $trip->netweight - $request->netweight;
        //         $trip->update(['netweight' => $newRemainingQuantity]);
        //     }
        // }

        return response()->json([
            'success' => true,
            'message' => 'Plant created successfully',
            'data' => $plant
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $plant = Plant::with(['trip', 'company:id,company_name', 'mine:id,mine_name', 'do:id,do_number'])->find($id);
       
         if ($plant->plant_attachment) {
                $plant->attachment_url = self::getUploadBaseUrl() . $plant->plant_attachment;
            } else {
                $plant->attachment_url = null;
            }
        if (!$plant) {
            return response()->json([
                'success' => false,
                'message' => 'Plant not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $plant
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $plant = Plant::find($id);

        if (!$plant) {
            return response()->json([
                'success' => false,
                'message' => 'Plant not found'
            ], 404);
        }

        $validator = \Validator::make($request->all(), [
            'type_of_mode_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

         // Handle file upload if present
            
            $doAttachmentName = null;
            if ($request->hasFile('file') && $request->file('file')->isValid()) {

                
                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $filename = pathinfo($originalName, PATHINFO_FILENAME);
                
                // Create upload directory if it doesn't exist
                $uploadPath = public_path('uploads/plants');
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
                if ($plant->plant_attachment) {
                    $oldFilePath = $uploadPath . '/' . $plant->plant_attachment;
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
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid base64 image data',
                            'error' => 'Failed to decode base64 image'
                        ], 422);
                    }
                    
                    // Create upload directory if it doesn't exist
                    $uploadPath = public_path('uploads/plants');
                    if (!file_exists($uploadPath)) {
                        if (!mkdir($uploadPath, 0755, true)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Failed to create upload directory',
                                'error' => 'Unable to create directory: ' . $uploadPath
                            ], 500);
                        }
                    }
                    
                    // Generate unique filename
                    $timestamp = date('YmdHis');
                    $newFilename = $timestamp . '_mobile_image.' . $imageType;
                    
                    // Save the image
                    if (file_put_contents($uploadPath . '/' . $newFilename, $binaryData) === false) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to save base64 image',
                            'error' => 'Unable to save image file'
                        ], 500);
                    }
                    
                    $doAttachmentName = $newFilename;
                    $request->merge(['mobile_image_status' => 1]);
                    
                    // Delete old file if exists
                    if ($plant->plant_attachment) {
                        $oldFilePath = $uploadPath . '/' . $plant->plant_attachment;
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid base64 image format',
                        'error' => 'Base64 image must start with data:image/'
                    ], 422);
                }
            }
        
            // Add attachment name to request data
            if ($doAttachmentName) {
                $request->merge(['plant_attachment' => $doAttachmentName]);
            }


        $plant->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Plant updated successfully',
            'data' => $plant
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $plant = Plant::find($id);
        
        if (!$plant) {
            return response()->json([
                'success' => false,
                'message' => 'Plant not found'
            ], 404);
        }

        // Start database transaction
        DB::beginTransaction();
        
        try {
            // Update trips table by adding back the plant's netweight
            // if (!empty($plant->trip_id)) {
            //     $updated = DB::update(
            //         'UPDATE trips SET netweight = netweight + ? WHERE id = ?',
            //         [$plant->netweight, $plant->trip_id]
            //     );
                
            //     Log::info('Trip updated after plant deletion', [
            //         'trip_id' => $plant->trip_id,
            //         'netweight_added' => $plant->netweight,
            //         'updated' => $updated
            //     ]);
            // }
            
            // Recalculate total_trips for all plants with the same do_id
            if (!empty($plant->do_id)) {
                $plants = Plant::where('do_id', $plant->do_id)
                                    ->orderBy('id')
                                    ->get();
                $tripCounter = 1;
                foreach ($plants as $plantRecord) {
                    $plantRecord->total_trips = $tripCounter;
                    $plantRecord->save();
                    $tripCounter++;
                }
                
                Log::info('Plant total_trips recalculated', [
                    'do_id' => $plant->do_id,
                    'total_plants' => count($plants)
                ]);
            }
            
            // Delete the plant record
            $plant->delete();
            
            // Commit transaction
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Plant deleted successfully and trip netweight updated'
            ]);
            
        } catch (\Exception $e) {
            // Rollback if any error occurs
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete plant',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
