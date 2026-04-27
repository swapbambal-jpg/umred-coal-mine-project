<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Auth\CustomLoginController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\ProductController;


Route::get('/clear-cache', function () {
    // Clear different types of caches
    Artisan::call('cache:clear');  // Clear application cache
    Artisan::call('config:clear'); // Clear config cache
    Artisan::call('route:clear');  // Clear route cache
    Artisan::call('view:clear');   // Clear compiled views

    return response()->json(['message' => 'Cache cleared successfully!']);
});

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/tables', [TableController::class, 'index'])->middleware('checkSession');
Route::get('/dashboard', [TableController::class, 'list'])->name('dashboard')->middleware('checkSession');

Route::get('/getProductItemById/{order_id}', [ProductController::class, 'getProductItemById']);

Route::post('/invoice/data', [ProductController::class, 'getInvoiceData'])->name('invoice')->middleware('checkSession');


Route::get('/checkout/{table_id}', [ProductController::class, 'checkout'])->name('checkout')->middleware('checkSession');


Route::get('/inventory/{id}/{restaurant_type}/{food_type?}', [ProductController::class, 'inventory'])
    ->name('inventory')
    ->middleware('checkSession');


Route::get('/stocks/{id}/{restaurant_type}/{food_type?}', [ProductController::class, 'stocks'])
    ->name('stocks')
    ->middleware('checkSession');

    
Route::get('/restaurants/{id}/{restaurant_type}/{food_type?}', [ProductController::class, 'restaurants'])
    ->name('restaurants')
    ->middleware('checkSession');


Route::get('/sale_report', [ProductController::class,'sale_report'])->name('sale_report')->middleware('checkSession');
Route::get('/stock_report', [ProductController::class,'stock_report'])->name('stock_report')->middleware('checkSession');



Route::get('/login', [CustomLoginController::class, 'showLoginForm'])->name('login');

Route::get('/', [CustomLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [CustomLoginController::class, 'login']);
Route::get('/logout', [CustomLoginController::class, 'logout'])->name('logout');

Route::prefix('products')->group(function () {

    Route::post('/create', [ProductController::class, 'store']);
    Route::post('/update/{product_id}', [ProductController::class, 'update']);    
    Route::get('/getProductById/{product_id}', [ProductController::class, 'get_product']);
    
    Route::get('/list/{table_id}/{cat_id}', 
        [ProductController::class, 'index']
    )->name('products.list');
    Route::post('/getList', [ProductController::class, 'getList']);
    Route::post('/addCounterFromStock', [ProductController::class,'addCounterFromStock']);
    Route::post('/getProductByAttributes', [ProductController::class,'getProductByAttributes']);

    Route::post('/add-counter', [ProductController::class,'addCounter'])->name('products.add-counter');;
});



