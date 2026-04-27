<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getDashboardCount(Request $request)
    {
        try {
            // Get total number of mines
            $totalMines = DB::table('mines')->count();
            $activeMines = DB::table('mines')->where('status', 'active')->count();
            $inactiveMines = $totalMines - $activeMines;

            // Get total number of companies
            $totalCompanies = DB::table('companies')->count();
            $activeCompanies = DB::table('companies')->where('status', 'active')->count();
            $inactiveCompanies = $totalCompanies - $activeCompanies;

            // Get total number of users
            $totalUsers = DB::table('users')->count();
            $activeUsers = DB::table('users')->where('status', 'active')->count();
            $inactiveUsers = $totalUsers - $activeUsers;

            // Get total number of delivery orders
            $totalDeliveryOrders = DB::table('delivery_orders')->count();
            $pendingDeliveryOrders = DB::table('delivery_orders')->where('status', 'pending')->count();
            $completedDeliveryOrders = DB::table('delivery_orders')->where('status', 'completed')->count();
            $inProgressDeliveryOrders = $totalDeliveryOrders - $pendingDeliveryOrders - $completedDeliveryOrders;

            


            return response()->json([
                'success' => true,
                'data' => [
                    'mines' => [
                        'total' => $totalMines,
                        'active' => $activeMines,
                        'inactive' => $inactiveMines
                    ],
                    'companies' => [
                        'total' => $totalCompanies,
                        'active' => $activeCompanies,
                        'inactive' => $inactiveCompanies
                    ],
                    'users' => [
                        'total' => $totalUsers,
                        'active' => $activeUsers,
                        'inactive' => $inactiveUsers
                    ],
                    'delivery_orders' => [
                        'total' => $totalDeliveryOrders,
                        'pending' => $pendingDeliveryOrders,
                        'completed' => $completedDeliveryOrders,
                        'in_progress' => $inProgressDeliveryOrders
                    ]
                ],
                'message' => 'Dashboard statistics retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
