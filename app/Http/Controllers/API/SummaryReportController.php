<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\DeliveryOrder;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SummaryReportController extends BaseController
{
    /**
     * Display a listing of the summary reports.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $query = DeliveryOrder::with([
                'size:id,name', 
                'grade:id,name', 
                'mine:id,mine_name', 
                'company:id,company_name',
                "trips:id,do_id,total_trips,netweight",
                "plants:id,do_id,total_trips,netweight"
            ]);

             $params = $request->input('params', []);


            if (!empty($params['do_id'])) {
                $query->where('id', $params['do_id']);
            }

            if (!empty($params['from_date'])) {
                $query->whereDate('created_at', '>=', $params['from_date']);
            }
            
            if (!empty($params['to_date'])) {
                $query->whereDate('created_at', '<=', $params['to_date']);
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

            // Get total count
            $total = $query->count();

            // Apply pagination
            $deliveryOrders = $query->select([
                        "id",
                        "do_number",
                        "do_actual_qty",
                        "expiry_date",
                        "size_id",
                        "grade_id",
                        "mine_id",
                        "company_id"
                        ])->orderBy('created_at', 'desc')
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
                'message' => 'Summary reports retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Summary Report Index Error: ' . $e->getMessage());
            return $this->sendError('Error retrieving summary reports.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Export summary reports without pagination.
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = DeliveryOrder::with([
                'size:id,name', 
                'grade:id,name', 
                'mine:id,mine_name', 
                'company:id,company_name',
                "trips:id,do_id,total_trips,netweight",
                "plants:id,do_id,total_trips,netweight"
            ]);

            // Filter by DO number if provided
            if ($request->has('do_number') && !empty($request->do_number)) {
                $query->where('do_number', 'like', '%' . $request->do_number . '%');
            }

            // Filter by mine name if provided
            if ($request->has('mine_name') && !empty($request->mine_name)) {
                $query->whereHas('mine', function($q) use ($request) {
                    $q->where('mine_name', 'like', '%' . $request->mine_name . '%');
                });
            }

            // Filter by from date if provided
            if ($request->has('from_date') && !empty($request->from_date)) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            
            // Filter by to date if provided
            if ($request->has('to_date') && !empty($request->to_date)) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $deliveryOrders = $query->select([
                        "id",
                        "do_number",
                        "do_actual_qty",
                        "expiry_date",
                        "size_id",
                        "grade_id",
                        "mine_id",
                        "company_id"
                        ])->get();
                        
            return $this->sendResponse($deliveryOrders, 'Summary reports exported successfully.');
        } catch (\Exception $e) {
            Log::error('Summary Report Export Error: ' . $e->getMessage());
            return $this->sendError('Error exporting summary reports.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified summary report.
     */
    public function show($do_number): JsonResponse
    {
        try {
            $deliveryOrder = DeliveryOrder::with([
                'size:id,name', 
                'grade:id,name', 
                'mine:id,mine_name', 
                'company:id,company_name'
            ])->where('do_number', $do_number)->first();

            if (!$deliveryOrder) {
                return $this->sendError('Delivery Order not found.');
            }

            $summaryData = $this->calculateSummaryData($deliveryOrder);
            
            if (!$summaryData) {
                return $this->sendError('No trip data found for this Delivery Order.');
            }

            return $this->sendResponse($summaryData, 'Summary report retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Summary Report Show Error: ' . $e->getMessage());
            return $this->sendError('Error retrieving summary report.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get summary statistics.
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $deliveryOrders = DeliveryOrder::with(['mine', 'company'])->get();
            $totalStats = [
                'total_delivery_orders' => 0,
                'total_do_qty' => 0,
                'total_lifting_qty' => 0,
                'total_plant_received_qty' => 0,
                'total_transit_qty' => 0,
                'average_balance_do_qty' => 0
            ];

            $balanceQtySum = 0;
            $validReportsCount = 0;

            foreach ($deliveryOrders as $do) {
                $summaryData = $this->calculateSummaryData($do);
                if ($summaryData) {
                    $totalStats['total_delivery_orders']++;
                    $totalStats['total_do_qty'] += $summaryData['do_qty'];
                    $totalStats['total_lifting_qty'] += $summaryData['lifting_qty'];
                    $totalStats['total_plant_received_qty'] += $summaryData['plant_received_qty'];
                    $totalStats['total_transit_qty'] += $summaryData['transit_qty'];
                    $balanceQtySum += $summaryData['balance_do_qty'];
                    $validReportsCount++;
                }
            }

            if ($validReportsCount > 0) {
                $totalStats['average_balance_do_qty'] = $balanceQtySum / $validReportsCount;
            }

            return $this->sendResponse($totalStats, 'Summary statistics retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Summary Statistics Error: ' . $e->getMessage());
            return $this->sendError('Error retrieving summary statistics.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Calculate summary data for a delivery order.
     */
    private function calculateSummaryData($deliveryOrder): ?array
    {
        try {
            // Get dispatch trips data
            $dispatchTrips = Trip::where('do_number', $deliveryOrder->do_number)
                ->where('trip_type', 'dispatch')
                ->selectRaw('COUNT(*) as trip_count, SUM(net_weight) as lifting_qty')
                ->first();

            // Get plant trips data
            $plantTrips = Trip::where('do_number', $deliveryOrder->do_number)
                ->where('trip_type', 'plant')
                ->selectRaw('COUNT(*) as trip_count, SUM(net_weight) as plant_received_qty')
                ->first();

            if (!$dispatchTrips || !$plantTrips) {
                return null;
            }

            // Calculate derived fields
            $doQty = $deliveryOrder->do_qty ?? 0;
            $liftingQty = $dispatchTrips->lifting_qty ?? 0;
            $plantReceivedQty = $plantTrips->plant_received_qty ?? 0;
            $dispatchTripCount = $dispatchTrips->trip_count ?? 0;
            $plantTripCount = $plantTrips->trip_count ?? 0;

            $balanceDoQty = $doQty - $liftingQty;
            $transitTotalTrips = $dispatchTripCount - $plantTripCount;
            $transitQty = $liftingQty - $plantReceivedQty;

            return [
                'do_number' => $deliveryOrder->do_number,
                'do_qty' => (float) $doQty,
                'mine_name' => $deliveryOrder->mine->mine_name ?? 'N/A',
                'dispatch_trips' => $dispatchTripCount,
                'lifting_qty' => (float) $liftingQty,
                'balance_do_qty' => (float) $balanceDoQty,
                'plant_trips' => $plantTripCount,
                'plant_received_qty' => (float) $plantReceivedQty,
                'transit_total_trips' => $transitTotalTrips,
                'transit_qty' => (float) $transitQty,
                'do_validity_date' => $deliveryOrder->do_validity_date ? $deliveryOrder->do_validity_date->format('Y-m-d') : null,
                'size_name' => $deliveryOrder->size->name ?? 'N/A',
                'grade_name' => $deliveryOrder->grade->name ?? 'N/A',
                'company_name' => $deliveryOrder->company->company_name ?? 'N/A'
            ];
        } catch (\Exception $e) {
            Log::error('Calculate Summary Data Error: ' . $e->getMessage());
            return null;
        }
    }


    public function getSummaryReports(Request $request): JsonResponse{

        try {

            $query = DeliveryOrder::with([
                    'size:id,name',
                    'grade:id,name',
                    'mine:id,mine_name',
                    'company:id,company_name',

                    // Trips in DESC order
                'trips' => function ($q) {
                    $q->select(
                        'id',
                        'do_id',
                        'delivery_challan_number',
                        'total_trips',
                        'truck_number',
                        'driver_name',
                        'truck_owner_name',
                        'gross_weight',
                        'tare_weight',
                        'netweight',
                        'destination_name',
                        'trip_date',
                        "truck_owner_name",
                        "remaining_quantity"
                    )->orderBy('id', 'desc');
                },

                // Plants in DESC order by trip_id
                'plants' => function ($q) {
                    $q->select(
                        'id',
                        'do_id',
                        'trip_id',
                        'plant_challan_number',
                        'gross_weight',
                        'tare_weight',
                        'netweight',
                        'sorted_exess',
                        'total_quantity_received',
                        'difference',
                        'destination_name',
                        'plant_received_date',
                        'total_trips'
                    )->orderBy('trip_id', 'desc');
                }
            ]);
            
            // Apply pagination
            $deliveryOrders = $query->select([
                        "id",
                        "do_number",
                        "total_quantity",
                        "party_name",
                        "accumulated_qty",
                        "expiry_date",
                        "do_actual_qty",
                        "expiry_date",
                        'issue_date',
                        "size_id",
                        "grade_id",
                        "mine_id",
                        "company_id",
                        "destination_name",
                        "created_at"
                        ])->where("id", $request->id)->get()->first();
                        
            
            // Create array with DO details repeated for each delivery challan number
            $summaryArray = [];
            
            if ($deliveryOrders && isset($deliveryOrders->trips)) {
                foreach ($deliveryOrders->trips as $trip) {
                    // Find corresponding plant record for this trip
                    $plantRecord = null;
                    if (isset($deliveryOrders->plants)) {
                        foreach ($deliveryOrders->plants as $plant) {
                            if ($plant->trip_id == $trip->id) {
                                $plantRecord = $plant;
                                break;
                            }
                        }
                    }
                    
                    // Calculate DO Balance
                    //print_r($trip->toArray());exit;
                    //$doBalance = ($deliveryOrders->do_actual_qty ?? 0) - ($deliveryOrders->accumulated_qty ?? 0);
                    
                    $summaryArray[] = [
                        'date' => $trip->trip_date ?? '',
                        'do_number' => $deliveryOrders->do_number ?? '',
                        'do_qty' => $deliveryOrders->do_actual_qty ?? '',
                        'Party_Name' => $deliveryOrders->party_name ?? '',
                        'Mine' => $deliveryOrders->mine->mine_name ?? '',
                        'Transporter' => $deliveryOrders->company->company_name ?? '',
                        'grade' => $deliveryOrders->grade->name ?? '',
                        'size' => $deliveryOrders->size->name ?? '',
                        'Dispatch_Challan_No' => $trip->delivery_challan_number ?? '',
                        'Dispatch_Trips' => $trip->total_trips ?? '',
                        'Truck_No' => $trip->truck_number ?? '',
                        "truck_owner_name"=> $trip->truck_owner_name ?? '',
                        'Dispatch_Gross_Weight' => $trip->gross_weight ?? '',
                        'Dispatch_Tare_Weight' => $trip->tare_weight ?? '',
                        'Dispatch_Net_Weight' => $trip->netweight ?? '',
                        'Do_Balance' => $trip->remaining_quantity,
                        'Lapse' => '', // You can specify what lapse means
                        'Destination' => $trip->destination_name ?? $deliveryOrders->destination_name ?? '',
                        'Do_Issue_Date' => $deliveryOrders->issue_date ?? '',
                        'Do_Expiry_Date' => $deliveryOrders->expiry_date ?? '',
                        'Plant_Challan_No' => $plantRecord->plant_challan_number ?? '',
                        'Plant_Received_Net_Weight' => $plantRecord->netweight ?? '',
                        'Sorted_excess' => $plantRecord->sorted_exess ?? '',
                        'Plant_Trips' => $plantRecord->total_trips ?? '',
                        'Plant_Balance' => $plantRecord->difference ?? ''
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                //'data' => $deliveryOrders,
                'data' => $summaryArray,
                'message' => 'Summary reports retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Summary Report Index Error: ' . $e->getMessage());
            return $this->sendError('Error retrieving summary reports.', ['error' => $e->getMessage()]);
        }
    }
}
