<?php
  
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\RestTablesController;
use App\Http\Controllers\API\MinesController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\CompaniesController;
use App\Http\Controllers\API\FcmTokenController;
use App\Http\Controllers\API\UsersController;
use App\Http\Controllers\API\DeliveryOrderController;
use App\Http\Controllers\API\TripsController;
use App\Http\Controllers\API\DoGradesController;
use App\Http\Controllers\API\TypeOfPurchasesController;
use App\Http\Controllers\API\TrucksController;
use App\Http\Controllers\API\SizesController;
use App\Http\Controllers\API\RolePermissionController;
use App\Http\Controllers\API\RoleMenuPermissionController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\CilSubsidiaryCompanyController;
use App\Http\Controllers\API\PlantController;
use App\Http\Controllers\API\YardsController;
use App\Http\Controllers\API\YardSourcesController;
use App\Http\Controllers\API\YardLocationsNameController;
use App\Http\Controllers\API\YardOutwardController;
use App\Http\Controllers\API\SummaryReportController;
use App\Http\Controllers\API\TruckFuelLogsController;


use Illuminate\Support\Facades\Artisan;
use App\Services\FcmService;

Route::post('/save-fcm-token', function (Request $request) {
    DB::table('fcm_tokens')->updateOrInsert(
        ['token' => $request->fcm_token],
        ['created_at' => now()]
    );
    return response()->json(['status' => 'token saved']);
});

Route::post('/send-test-push', function (Request $request, FcmService $fcm) {

    if (!$request->fcm_token) {
        return response()->json(['error' => 'Token required'], 422);
    }
    $fcm->sendPush(
        $request->fcm_token,
        'Direct Push',
        'No auth, direct token 🚀'
    );

    return response()->json(['status' => 'Push sent']);
});


Route::middleware(['api.auth', 'auth:api'])->prefix('mines')->group(function () {
    Route::post('/getList', [MinesController::class, 'index']);
    Route::post('/create', [MinesController::class, 'store']);
    Route::get('/view/{id}', [MinesController::class, 'showMine']);
    Route::put('/update/{id}', [MinesController::class, 'updateMine']);
    Route::delete('/delete/{id}', [MinesController::class, 'destroyMine']);
    Route::post('/getMineList', [MinesController::class, 'getMineList']);
});



Route::middleware(['api.auth', 'auth:api'])->prefix('companies')->group(function () {
    Route::post('/getList', [CompaniesController::class, 'index']);
    Route::post('/create', [CompaniesController::class, 'store']);
    Route::get('/view/{id}', [CompaniesController::class, 'show']);
    Route::put('/update/{id}', [CompaniesController::class, 'update']);
    Route::delete('/delete/{id}', [CompaniesController::class, 'destroy']);
    Route::post('/getCompanyList', [CompaniesController::class, 'getCompanyList']);
    Route::get('/active/list', [CompaniesController::class, 'activeCompanies']);
});


Route::middleware(['api.auth', 'auth:api'])->prefix('roles')->group(function () {
    Route::get('/getList', [RoleController::class, 'index']);
    Route::post('/create', [RoleController::class, 'store']);
    Route::get('/view/{id}', [RoleController::class, 'show']);
    Route::put('/update/{id}', [RoleController::class, 'update']);
    Route::delete('/delete/{id}', [RoleController::class, 'destroy']);
    Route::get('/active/list', [RoleController::class, 'activeRoles']);
    
});

Route::middleware(['api.auth', 'auth:api'])->prefix('role_permissions')->group(function () {
    Route::post('/getList', [RolePermissionController::class, 'index']);
    Route::post('/create', [RolePermissionController::class, 'store']);
    Route::get('/view/{id}', [RolePermissionController::class, 'show']);
    Route::put('/update/{id}', [RolePermissionController::class, 'update']);
    Route::delete('/delete/{id}', [RolePermissionController::class, 'destroy']);
    Route::get('/rolePermissionList', [RolePermissionController::class, 'getRolePermissionList']);
    Route::get('/getByRole/{roleId}', [RolePermissionController::class, 'getPermissionByRole']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('role_menu_permissions')->group(function () {
    Route::post('/getList', [RoleMenuPermissionController::class, 'index']);
    Route::post('/create', [RoleMenuPermissionController::class, 'store']);
    Route::get('/view/{id}', [RoleMenuPermissionController::class, 'show']);
    Route::put('/update/{id}', [RoleMenuPermissionController::class, 'update']);
    Route::delete('/delete/{id}', [RoleMenuPermissionController::class, 'destroy']);
    Route::get('/roleMenuPermissionList', [RoleMenuPermissionController::class, 'getRoleMenuPermissionList']);
    Route::get('/getByRole/{roleId}', [RoleMenuPermissionController::class, 'getPermissionsByRole']);
    Route::get('/getMenusWithPermissions/{roleId}', [RoleMenuPermissionController::class, 'getMenusWithPermissions']);
});


Route::middleware(['api.auth', 'auth:api'])->prefix('users')->group(function () {
    Route::post('/getList', [UsersController::class, 'index']);
    Route::post('/create', [UsersController::class, 'store']);
    Route::get('/view/{id}', [UsersController::class, 'show']);
    Route::post('/update/{id}', [UsersController::class, 'update']);
    Route::post('/update', [UsersController::class, 'updateProfile']);
    Route::delete('/delete/{id}', [UsersController::class, 'destroy']);
    Route::get('/userList', [UsersController::class, 'getUserList']);
    Route::get('/userDropdown', [UsersController::class, 'getUserDropdown']);
    Route::get('/roleList', [UsersController::class, 'getRoleList']);
    Route::get('/profile', [UsersController::class, 'profile']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('menus')->group(function () {
    Route::post('/getList', [MenuController::class, 'index']);
    Route::post('/create', [MenuController::class, 'store']);
    Route::get('/view/{id}', [MenuController::class, 'show']);
    Route::put('/update/{id}', [MenuController::class, 'update']);
    Route::delete('/delete/{id}', [MenuController::class, 'destroy']);
    Route::get('/tree', [MenuController::class, 'tree']);
    Route::patch('/toggle/{id}', [MenuController::class, 'toggleStatus']);
    Route::get('/parentList', [MenuController::class, 'getParentMenuList']);
    Route::get('/parentSubmenuList', [MenuController::class, 'getParentSubmenuList']);
    
    // New hierarchical menu endpoints
    Route::post('/complete-tree', [MenuController::class, 'getCompleteTree']);
    Route::get('/subtree/{menuId}', [MenuController::class, 'getSubtree']);
    Route::get('/children/{parentId}', [MenuController::class, 'getChildren']);
    Route::get('/ancestors/{menuId}', [MenuController::class, 'getAncestors']);
    Route::get('/descendants/{menuId}', [MenuController::class, 'getDescendants']);
    Route::get('/breadcrumb/{menuId}', [MenuController::class, 'getBreadcrumb']);
    Route::put('/move/{menuId}', [MenuController::class, 'moveMenu']);
    Route::delete('/delete-with-children/{menuId}', [MenuController::class, 'destroyWithChildren']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('delivery_orders')->group(function () {
    
    Route::post('/getList', [DeliveryOrderController::class, 'index']);
    Route::post('/create', [DeliveryOrderController::class, 'store']);
    Route::get('/view/{id}', [DeliveryOrderController::class, 'show']);
    Route::put('/update/{id}', [DeliveryOrderController::class, 'update']);
    Route::delete('/delete/{id}', [DeliveryOrderController::class, 'destroy']);
    Route::get('/deliveryOrderList', [DeliveryOrderController::class, 'getDeliveryOrderList']);
    Route::post('/getDoByMineId', [DeliveryOrderController::class, 'getDeliveryOrdersByMine']);
    Route::post('/getDoNumberList', [DeliveryOrderController::class, 'getDoNumberList']);
    Route::put('/updateStatus/{id}', [DeliveryOrderController::class, 'updateStatus']);

    Route::post('/deleteTripAttachment', [DeliveryOrderController::class, 'deleteTripAttachment']);


    
});

Route::middleware(['api.auth', 'auth:api'])->prefix('trips')->group(function () {
    
    Route::post('/getList', [TripsController::class, 'index']);
    Route::post('/create', [TripsController::class, 'store']);
    Route::get('/view/{id}', [TripsController::class, 'show']);
    Route::put('/update/{id}', [TripsController::class, 'update']);
    Route::delete('/delete/{id}', [TripsController::class, 'destroy']);
    Route::get('/tripsList', [TripsController::class, 'getTripsList']);
    Route::match(['get', 'post'], '/getTripChallanList', [TripsController::class, 'getTripChallanList']);
    Route::post('/getDeliveryChallanByDoId', [TripsController::class, 'getDeliveryChallanByDoId']);
    Route::post('/deleteTripAttachment', [TripsController::class, 'deleteTripAttachment']);
    Route::get('/getTripsRecordByChallanNumber/{id}', [TripsController::class, 'getTripsRecordByChallanNumber']);
    Route::match(['get', 'post'], '/calculateTripsweight', [TripsController::class, 'calculateTripsweight']);

    
});

Route::middleware(['api.auth', 'auth:api'])->prefix('do_grades')->group(function () {
    
    Route::post('/getList', [DoGradesController::class, 'index']);
    Route::post('/create', [DoGradesController::class, 'store']);
    Route::get('/view/{id}', [DoGradesController::class, 'show']);
    Route::put('/update/{id}', [DoGradesController::class, 'update']);
    Route::delete('/delete/{id}', [DoGradesController::class, 'destroy']);
});

// Public route for dropdown list
Route::get('/do_grades/doGradesList', [DoGradesController::class, 'getDoGradesList']);

// Public route for sizes dropdown list
Route::match(['get', 'post'], '/sizes/sizesList', [SizesController::class, 'getSizesList']);
Route::match(['get', 'post'], '/sizes/getSizeList', [SizesController::class, 'getSizesList']);

Route::middleware(['api.auth', 'auth:api'])->prefix('type_of_purchases')->group(function () {
    
    Route::post('/getList', [TypeOfPurchasesController::class, 'index']);
    Route::post('/create', [TypeOfPurchasesController::class, 'store']);
    Route::get('/view/{id}', [TypeOfPurchasesController::class, 'show']);
    Route::put('/update/{id}', [TypeOfPurchasesController::class, 'update']);
    Route::delete('/delete/{id}', [TypeOfPurchasesController::class, 'destroy']);
    Route::get('/typeOfPurchasesList', [TypeOfPurchasesController::class, 'getTypeOfPurchasesList']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('trucks')->group(function () {
    Route::post('/getList', [TrucksController::class, 'index']);
    Route::post('/create', [TrucksController::class, 'store']);
    Route::get('/view/{id}', [TrucksController::class, 'show']);
    Route::put('/update/{id}', [TrucksController::class, 'update']);
    Route::delete('/delete/{id}', [TrucksController::class, 'destroy']);
    Route::get('/trucksList', [TrucksController::class, 'index']);
    Route::post('/getTruckDetailsByModel', [TrucksController::class, 'getTruckDetailsByModel']);
    Route::post('/getGateDetailsByVehicleNo', [TrucksController::class, 'getGateDetailsByVehicleNo']);
    Route::get('/truckList', [TrucksController::class, 'getTruckList']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('truck_fuel_logs')->group(function () {
    Route::post('/getList', [TruckFuelLogsController::class, 'index']);
    Route::post('/create', [TruckFuelLogsController::class, 'store']);
    Route::get('/view/{id}', [TruckFuelLogsController::class, 'show']);
    Route::post('/update', [TruckFuelLogsController::class, 'update']);
    Route::get('/delete/{id}', [TruckFuelLogsController::class, 'destroy']);
    Route::post('/getFuelLogsByTruck', [TruckFuelLogsController::class, 'getFuelLogsByTruck']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('sizes')->group(function () {
    Route::post('/getList', [SizesController::class, 'index']);
    Route::post('/create', [SizesController::class, 'store']);
    Route::get('/view/{id}', [SizesController::class, 'show']);
    Route::put('/update/{id}', [SizesController::class, 'update']);
    Route::delete('/delete/{id}', [SizesController::class, 'destroy']);
    Route::get('/sizesList', [SizesController::class, 'getSizesList']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('dashboard')->group(function () {
    Route::get('/getDashboardCount', [DashboardController::class, 'getDashboardCount']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('cil_subsidiary')->group(function () {
    Route::get('/getList', [CilSubsidiaryCompanyController::class, 'index']);
    Route::post('/create', [CilSubsidiaryCompanyController::class, 'store']);
    Route::get('/view/{id}', [CilSubsidiaryCompanyController::class, 'show']);
    Route::put('/update/{id}', [CilSubsidiaryCompanyController::class, 'update']);
    Route::delete('/delete/{id}', [CilSubsidiaryCompanyController::class, 'destroy']);
    Route::get('/getSubsidiaryList', [CilSubsidiaryCompanyController::class, 'getCompanyList']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('plants')->group(function () {
    Route::post('/getList', [PlantController::class, 'index']);
    Route::post('/create', [PlantController::class, 'store']);
    Route::get('/view/{id}', [PlantController::class, 'show']);
    Route::post('/update/{id}', [PlantController::class, 'update']);
    Route::delete('/delete/{id}', [PlantController::class, 'destroy']);
    Route::get('/plantsList', [PlantController::class, 'index']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('yards')->group(function () {
    Route::post('/getList', [YardsController::class, 'index']);
    Route::post('/create', [YardsController::class, 'store']);
    Route::post('/saveYardRecord', [YardsController::class, 'saveYardRecord']);
    Route::get('/view/{id}', [YardsController::class, 'show']);
    Route::put('/update/{id}', [YardsController::class, 'update']);
    Route::delete('/delete/{id}', [YardsController::class, 'destroy']);
    Route::get('/yardsList', [YardsController::class, 'index']);
    Route::post('/getYardDoNumber', [YardsController::class, 'getYardDoNumber']);
    Route::post('/getYardRecordByDoORYard', [YardsController::class, 'getYardRecordByDoORYard']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('yard_sources')->group(function () {
    Route::post('/getList', [YardSourcesController::class, 'index']);
    Route::post('/create', [YardSourcesController::class, 'store']);
    Route::get('/view/{id}', [YardSourcesController::class, 'show']);
    Route::put('/update/{id}', [YardSourcesController::class, 'update']);
    Route::delete('/delete/{id}', [YardSourcesController::class, 'destroy']);
    Route::get('/getYardSourcesList', [YardSourcesController::class, 'getYardSourcesList']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('yard_destinations')->group(function () {
    Route::post('/getList', [YardLocationsNameController::class, 'index']);
    Route::post('/create', [YardLocationsNameController::class, 'store']);
    Route::get('/view/{id}', [YardLocationsNameController::class, 'show']);
    Route::put('/update/{id}', [YardLocationsNameController::class, 'update']);
    Route::delete('/delete/{id}', [YardLocationsNameController::class, 'destroy']);
    Route::get('/getYardDestinationList', [YardLocationsNameController::class, 'getYardLocationsNameList']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('yard_outwards')->group(function () {
    Route::post('/getList', [YardOutwardController::class, 'index']);
    Route::post('/create', [YardOutwardController::class, 'store']);
    Route::get('/view/{id}', [YardOutwardController::class, 'show']);
    Route::put('/update/{id}', [YardOutwardController::class, 'update']);
    Route::delete('/delete/{id}', [YardOutwardController::class, 'destroy']);
    Route::post('/getYardDoNumber', [YardOutwardController::class, 'getYardDoNumber']);
 
    Route::get('/yardOutwardsList', [YardOutwardController::class, 'getYardOutwardsList']);
});

Route::middleware(['api.auth', 'auth:api'])->prefix('summary_reports')->group(function () {
    
    Route::post('/getList', [SummaryReportController::class, 'index']);
    Route::post('/export', [SummaryReportController::class, 'export']);
    Route::get('/view/{do_number}', [SummaryReportController::class, 'show']);
    Route::get('/statistics', [SummaryReportController::class, 'getStatistics']);
    Route::post('/getSummaryReports', [SummaryReportController::class, 'getSummaryReports']);

    
});

Route::post('/fcm-token', [FcmTokenController::class, 'store'])
    ->middleware('auth:sanctum');


Route::get('/clear-cache', function () {
    // Clear different types of caches
    Artisan::call('cache:clear');  // Clear application cache
    Artisan::call('config:clear'); // Clear config cache
    Artisan::call('route:clear');  // Clear route cache
    Artisan::call('view:clear');   // Clear compiled views

    return redirect()->route('success.page'); // Redirect to another view
});

// Route for the success page
Route::get('/cache-cleared', function () {
    return view('cache_cleared'); // View file: resources/views/cache_cleared.blade.php
})->name('success.page');


 
Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [RegisterController::class, 'login']);
Route::get('server-datetime', [RegisterController::class, 'serverDateTime']);
     
/*Route::middleware('auth:api')->group( function () {
   Route::get('categories', [CategoryController::class, 'index']);
   Route::post('categories/create', [CategoryController::class, 'store']);
   Route::get('categories/view/{category}', [CategoryController::class, 'show']);
   Route::put('categories/update/{category}', [CategoryController::class, 'update']);  // Update category
   Route::delete('categories/delete/{category}', [CategoryController::class, 'destroy']); // Delete category

});*/




