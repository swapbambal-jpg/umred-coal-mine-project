<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\Category;
use App\Models\ProductMlPrice;
use App\Models\CounterProductStock;
use App\Models\StockReport;



use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{


    public function getProductItemById(Request $request, $order_id)
    {
        $products = Order::select("id")->with([
                        'order_item:id,order_id,product_id,quantity,total_price,price',  // Only these fields
                        'order_item.product:id,name,price,quantity' 
             ])->where('id', $order_id)->first();
         // print_r($products->toArray());exit();                  
        return view('products_list', ['products' => $products]);
    }



    public function index(Request $request, $table_id, $cat_id = 1)
    {
        // API call for products
        $response = Http::get("https://restaurant.keninfotec.com/api/products/getList/{$cat_id}");
        $responseData = $response->json();
        $products = $responseData['data'] ?? []; // fallback to empty array if "data" missing

        // API call for cart list
        $response_c = Http::post(
            'https://restaurant.keninfotec.com/api/rest_tables/getCartList',
            [
                "table_id" => $table_id,
                "user_id" => session()->get("user_id") // safer than session()->all()
            ]
        );

        $responseCart = $response_c->json();
        $cartList = $responseCart['data'] ?? []; // fallback

        return view('products', [
            'products' => $products,
            'table_id' => $table_id,
            'cat_id' => $cat_id,
            'cartList' => count($cartList)
        ]);
    }



    public function getList(Request $request){

        $cat_id = $request->cat_id ;
        $product_name= !empty($request->product_name)?$request->product_name:"" ;

        $response = Http::post('https://restaurant.keninfotec.com/api/products/getList',["restaurant_type"=>$cat_id,"product_name"=>$product_name]); // Replace with actual API 
        $products = $response->json()['data'];

        return view('get-list', ['products' => $products,"cat_id"=>$cat_id]);
    }



    public function checkout(Request $request,$table_id)
    {
        $response = Http::post('https://restaurant.keninfotec.com/api/rest_tables/getCartList',["table_id"=>$table_id,"user_id"=>session()->all()["user_id"]]); //Replace with actual API URL
        $products = $response->json()['data'];
        $cart_response = Http::get('https://restaurant.keninfotec.com/api/rest_tables/getCartList/'.$table_id."/".session()->all()["user_id"]); // Replace with actual API URL
        $cartList = $response->json()['data'];

        return view('checkout', ['products' => $products,"table_id"=>$table_id,"cartList"=>count($cartList),"user_id"=>session()->all()["user_id"]]);
    }


    public function inventory(Request $request,$cat_id=1,$restaurant_type=1,$food_type="veg")
    {

         $name="";

         $products = Product::with('product_prices')
                                    ->where('restaurant_type', 2)
                                    ->whereHas('product_prices')   // 👈 only products having prices
                                    ->get()
                                    ->toArray();

        $categories =   Category::where('rest_type', $restaurant_type)->get()->toArray();
       
        return view('inventory', ['products' => $products,"table_id"=>7,"cat_id"=>$cat_id,"restaurant_type"=>$restaurant_type,"categories"=>$categories,"food_type"=>$food_type]);
    }


    public function stocks(Request $request,$cat_id=1,$restaurant_type=1,$food_type="veg"){

        if(!empty($cat_id)) {
          $products = Product::with(["counter_product_stocks"])->where(['restaurant_type'=>2,'cat_id'=>$cat_id])->get()->toArray();
        }else {
          $products = Product::with(["counter_product_stocks"])->where(['restaurant_type'=>2])->get()->toArray();
            
        }
        $categories =   Category::where('rest_type', $restaurant_type)->get()->toArray();
        return view('stocks', ['products' => $products,"table_id"=>7,"cat_id"=>$cat_id,"restaurant_type"=>$restaurant_type,"categories"=>$categories,"food_type"=>$food_type]);
    }


    public function restaurants(Request $request,$cat_id=1,$restaurant_type=1,$food_type="veg")
    {

         $name="";
         if($restaurant_type==1) {
            $products = Product::where('cat_id', $cat_id)
                                    ->where('restaurant_type', $restaurant_type)
                                    ->when($food_type && $restaurant_type == 1, function($query) use ($food_type) {
                                        return $query->where('food_type', $food_type);
                                    })
                                    ->when(!empty($name), function($query) use ($name) {
                                        return $query->where('name', 'LIKE', '%'.$name.'%');
                                    })
                                    ->get();

        }else {

            $products = Product::where('restaurant_type', $restaurant_type)
                                    ->get();
        }                                    

        $categories =   Category::where('rest_type', $restaurant_type)->get()->toArray();
       
        return view('restaurants', ['products' => $products,"table_id"=>7,"cat_id"=>$cat_id,"restaurant_type"=>$restaurant_type,"categories"=>$categories,"food_type"=>$food_type]);
    }

    public function sale_report(Request $request){

        $reportType = "yearly";
        $product_id = "";
        $startDate = "";
        $endDate = "";

        $products = Product::pluck('name', 'id')->toArray();

        $query = Order::with([
            "rest_table:id,name",
            'order_item' => function ($q) use ($product_id, $reportType, $startDate, $endDate) {
                if ($product_id) {
                    $q->where('product_id', $product_id);
                }

                // Load product details
                $q->with('product:id,name,price,quantity,unit,restaurant_type');

                // Apply report type condition
                switch ($reportType) {
                    case 'daily':
                        $q->whereDate('created_at', Carbon::today());
                        break;

                    case 'weekly':
                        $q->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                        break;

                    case 'monthly':
                        $q->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                        break;

                    case 'yearly':
                        $q->whereYear('created_at', Carbon::now()->year);
                        break;

                    default:
                        throw new \Exception('Invalid report type');
                }

                // Apply start_date and end_date if provided
                if ($startDate && $endDate) {
                    $q->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                } elseif ($startDate) {
                    $q->whereDate('created_at', '>=', $startDate);
                } elseif ($endDate) {
                    $q->whereDate('created_at', '<=', $endDate);
                }

            }
        ])->whereHas('order_item', function ($q) use ($product_id, $reportType, $startDate, $endDate) {
            if ($product_id) {
                $q->where('product_id', $product_id);
            }

            // Apply report type condition
            switch ($reportType) {
                case 'daily':
                    $q->whereDate('created_at', Carbon::today());
                    break;

                case 'weekly':
                    $q->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;

                case 'monthly':
                    $q->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                    break;

                case 'yearly':
                    $q->whereYear('created_at', Carbon::now()->year);
                    break;
            }

            // Apply start_date and end_date if provided
            if ($startDate && $endDate) {
                $q->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
            } elseif ($startDate) {
                $q->whereDate('created_at', '>=', $startDate);
            } elseif ($endDate) {
                $q->whereDate('created_at', '<=', $endDate);
            }
    
        });

        $orders = $query->orderBy('id', 'desc')->get();
        $totalSales = $orders->sum('total_price');
        $totalOrders = $orders->count();
        
        return view('sale_report', [
                'orders' => $orders,
                'total_orders' => $totalOrders,
                'total_sales' => $totalSales,
                'products'=>$products,
                "user_id"=>session()->all()["user_id"]]);
    }

    public function stock_report(Request $request){

        $reportType = "yearly";
        $product_id = "";
        $startDate = "";
        $endDate = "";

        $products = Product::pluck('name', 'id')->toArray();

        $orders = StockReport::select(["id","product_id"])->with('product:id,name')
                    ->whereIn('id', function ($q) {
                        $q->selectRaw('MAX(id)')
                          ->from('stock_reports')
                          ->groupBy('product_id');
                    })
                    ->orderBy('id', 'desc')
                    ->get();

        $totalSales = $orders->sum('total_price');
        $totalOrders = $orders->count();
        
        return view('stock_report', [
                'orders' => $orders,
                'total_orders' => $totalOrders,
                'total_sales' => $totalSales,
                'products'=>$products,
                "user_id"=>session()->all()["user_id"]]);
    }


    public function stock_report_old(Request $request)
    {
        $restaurant_type=1;
        $id=7;
        $food_type="1";
        $id=2;
        $category = Category::where('id', $id)->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        
        if($food_type==1){
            $foodType="veg";
        }else {
            $foodType="nonveg";
        }
        
        
        $products = Product::when($foodType && $restaurant_type == 1, function($query) use ($foodType) {
                            return $query->where('food_type', $foodType);
                        })
                        ->when(!empty($name), function($query) use ($name) {
                            return $query->where('name', 'LIKE', '%'.$name.'%');
                        })
                        ->get();
    
        /*$response = Http::get('https://restaurant.keninfotec.com/api/products/getOrderProducts/2/'.$restaurant_type); // Replace with actual API URL
        $products = $response->json()['data'];*/
        print_r($products);exit();
        return view('stock_report', ['products' => $products,"table_id"=>$id,"cat_id"=>$restaurant_type]);
    }

    public function getInvoiceData(Request $request)
    {

       $table_id =$request->all()["table_id"];

        $response = Http::post('https://restaurant.keninfotec.com/api/rest_tables/getCartList',["table_id"=>$table_id,"user_id"=>session()->all()["user_id"]]); //Replace with actual API URL
        

        $productRec = $response->json()['data'];
        
        $products=[];

        foreach($productRec as $row) {
            $products[]=["name"=>$row["product"]["name"],"price"=>$row["price"],"qty"=>$row["quantity"]];
        }


        $grandTotal = collect($products)->sum(fn($item) => $item['price'] * $item['qty']);

        return view('invoice.partials.invoice_table', compact('products', 'grandTotal'))->render();
    }


    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'name' => 'required|string',
            /*'quantity' => 'required|integer|min:1',
            'unit' => 'required|string|max:10',*/
        ]);

        if ($validator->fails()) {
            return response()->json(["message"=>"The name has already been taken.","code"=>500], 422);
        }
         $exists = Product::where('name', $request->name)->where('cat_id', $request->cat_id)->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'The product name already exists in this category.']);
        }


        $data = $request->all();
        //Copy request data
         // Handle base64 image upload
        if (!empty($request->image)) {
            $image = $request->image;
            $image_parts = explode(";base64,", $image);

            if (count($image_parts) == 2) {
                $image_type_aux = explode("image/", $image_parts[0]);
                if (count($image_type_aux) == 2) {
                    $image_type = $image_type_aux[1];
                    $image_base64 = base64_decode($image_parts[1]);

                    // Create unique filename
                    $fileName = 'product_' . time() . '.' . $image_type;
                    $filePath = 'products/' . $fileName;

                    // Save image to storage/app/public/products
                    Storage::disk('public')->put($filePath, $image_base64);

                    // Store file path in database
                    $data['image'] = $filePath;
                }
            }
        }

        //print_r($data);exit;

        $product = Product::create($data);

        $lastId = $product->id;
        
        if($request->all()["restaurant_type"]==2) {
            $mlFields = [
                30  => '30',
                60  => '60',
                90  => '90',
                180 => '180',
                200  => '200',
                330  => '330',
                375  => '375',
                650=>'650',
                750 => '750',
            ];

            CounterProductStock::where('product_id', $lastId)->delete();
            //print_r($request->all());exit;
            $create_price=[];
            foreach ($mlFields as $ml => $input) {
                $create_price[]=[
                            "product_id"=>$lastId,
                            "ml_size"=>$ml,
                            "selling_price"=>$request->all()["ml_selling_price"][$ml],
                            "mrp_price"=>$request->all()["ml_price"][$ml],
                            "quanity"=>$request->all()["ml_quanity"][$ml],
                        ];
            }

            if (!empty($create_price)) {
                    CounterProductStock::insert($create_price);
            }
        }

        return response()->json(["code"=>200,'message' => 'Product created successfully']);
    }
    
    
    public function update(Request $request, $id){

        //Check if a product with the same category_id and code exists (excluding the current product)
        $duplicate = Product::where('cat_id', $request->cat_id)
                            ->where('name', $request->name)
                            ->where('id', '!=', $id)
                            ->first();
                            
        if ($duplicate) {
            // If a duplicate record is found, return an error response
            return response()->json(['message' => 'Duplicate product found with the same category_id and code.',"code"=>500], 400);
        }

        $requestData =$request->all();

        // Find the product by id
        $product = Product::findOrFail($id);

        // Handle base64 image upload if provided
        
        // If a new image is uploaded
        if ($request->hasFile('image')) {

            // 1. Delete old image if exists
            if (!empty($product->image)) {
                $oldImagePath = public_path('products/images/' . $product->image);

                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);   // delete old file
                }
            }

            // 2. Upload new image
            $image     = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();

            $image->move(public_path('products/images'), $imageName);

            // Update request data
             $requestData['image'] = $imageName;
        } else {
            // If no new image uploaded, keep old image
            unset($requestData['image']);
        }

        // Update the product with the request data
        $product->update($requestData);

        if($request->all()["restaurant_type"]==2) {
        
            $create_price=[];
            $mlFields = [
                30  => '30',
                60  => '60',
                90  => '90',
                180 => '180',
                200  => '200',
                330  => '330',
                375  => '375',
                650=>'650',
                750 => '750',
            ];

            foreach ($mlFields as $ml => $input) {
                $create_price[]=[
                            "product_id"=>$id,
                            "ml_size"=>$ml,
                            "selling_price"=>$request->all()["ml_selling_price"][$ml],
                            "mrp_price"=>$request->all()["ml_price"][$ml],
                            "quanity"=>$request->all()["ml_quanity"][$ml],
                        ];
            }

            CounterProductStock::where('product_id', $id)->delete();
            if (!empty($create_price)) {
                    CounterProductStock::insert($create_price);
            }

        }    


        // Return the updated product
        
            return response()->json([
                'message' => 'Cart updated successfully',
                'code' => 200,
            ], 200);
    }

    public function get_product(Request $request, $id){

        $product = Product::where('id', $id)->first(); // Use first() instead of get()
        $product_price = CounterProductStock::where('product_id', $id)->get()->toArray(); // Use first() instead of get()
            
        /*$ml_selling_90=0;
        $ml_selling_180=0;
        $ml_selling_750=0;

        $ml_price_90=0;
        $ml_price_180=0;
        $ml_price_750=0;

        $ml_qty_90=0;
        $ml_qty_180=0;
        $ml_qty_750=0;*/

        foreach ($product_price as $key => $row) {

            switch ($row["ml_size"]) {

                case "30":
                case 30:
                    $product->ml_selling_30 = $row["selling_price"];
                    $product->ml_price_30   = $row["mrp_price"];
                    $product->ml_qty_30     = $row["quanity"];
                    break;


                case "60":
                case 60:
                    $product->ml_selling_60 = $row["selling_price"];
                    $product->ml_price_60   = $row["mrp_price"];
                    $product->ml_qty_60     = $row["quanity"];
                    break;

                case "90":
                case 90:
                    $product->ml_selling_90 = $row["selling_price"];
                    $product->ml_price_90   = $row["mrp_price"];
                    $product->ml_qty_90     = $row["quanity"];
                    break;

                case "180":
                case 180:
                    $product->ml_selling_180 = $row["selling_price"];
                    $product->ml_price_180   = $row["mrp_price"];
                    $product->ml_qty_180     = $row["quanity"];
                    break;

                case "200":
                case 200:
                    $product->ml_selling_200 = $row["selling_price"];
                    $product->ml_price_200   = $row["mrp_price"];
                    $product->ml_qty_200     = $row["quanity"];
                    break;
                

                case "330":
                case 330:
                    $product->ml_selling_330 = $row["selling_price"];
                    $product->ml_price_330   = $row["mrp_price"];
                    $product->ml_qty_330     = $row["quanity"];
                    break;

                
                case "375":
                case 375:
                    $product->ml_selling_375 = $row["selling_price"];
                    $product->ml_price_375   = $row["mrp_price"];
                    $product->ml_qty_375     = $row["quanity"];
                    break;

                case "650":
                case 650:
                    $product->ml_selling_650 = $row["selling_price"];
                    $product->ml_price_650   = $row["mrp_price"];
                    $product->ml_qty_650     = $row["quanity"];
                    break;


                case "750":
                case 750:
                    $product->ml_selling_750 = $row["selling_price"];
                    $product->ml_price_750   = $row["mrp_price"];
                    $product->ml_qty_750     = $row["quanity"];
                    break;
            }
        }




         if (!$product) {
            return response()->json([
                "data" => null,
                "code" => 404,
                "message" => "Product not found"
            ]);
        }

        return response()->json([
            "data" => $product,
            "code" => 200,
            "message" => "Get Product successfully"
        ]);
    }


    public function addCounterFromStock(Request $request){

        $products = Product::select(["products.name","products.id"])->with(["product_prices"])->where('restaurant_type',2)->get()->toArray();
        return view('add_counter_from_stocks', ['products' => $products]);
    }

    public function getProductByAttributes(Request $request){
        $products = Product::select(["products.name","products.id"])->with(["available_product_prices"])->where('id',$request->product_id)->get()->first()->toArray();
        return view('get_product_by_attributes', ['products' => $products]);
    }

    public function addCounter(Request $request){

        $counter_stock=[];

        $product_id = $request->all()["select_product"];

        foreach($request->all()["new_quanity"] as $key=>$row) {

            $result = ProductMlPrice::select(['id', 'quanity'])
                                    ->where('product_id', $product_id)
                                    ->where('stock_id', $key)
                                    ->first();


                $mrp_price     = $request->mrp_price[$key] ?? 0;
                $selling_price= $request->selling_price[$key] ?? 0;
                $quanity       = $request->quanity[$key] ?? 0;
                $ml_size       = $request->ml_size[$key] ?? null;

            if ($result) {
                $result->update([
                    'stock_id'      => $key,
                    'product_id'    => $product_id,
                    'mrp_price'     => $mrp_price,
                    'selling_price' => $selling_price,
                    'quanity'       => $result->quanity + $row,
                    'ml_size'       => $ml_size
                ]);
            } else {
                
                ProductMlPrice::create([
                    'stock_id'      => $key,
                    'product_id'    => $product_id,
                    'mrp_price'     => $mrp_price,
                    'selling_price' => $selling_price,
                    'quanity'       => $row,
                    'ml_size'       => $ml_size,
                ]);
            }

            $result_stocks = CounterProductStock::select(['id', 'quanity'])
                                    ->where('id', $key)
                                    ->first();
            if ($result_stocks) {
                $total_quantity =$result_stocks->quanity-$row;

                $result_stocks->update(['quanity'=>$total_quantity]);
            }

           
        }

        echo json_encode(["status"=>200]);exit;
    }




}
