<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Http\Resources\Api\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\Cart;

use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductMlPrice;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use App\Models\CounterProductStock;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $restaurant_type = $request->restaurant_type;
        $search = $request->product_name;
        $category_id = $request->category_id;

        $productResult = Product::with(["product_prices","categories"])->where('restaurant_type',$restaurant_type)->when($search, function ($query, $search) {
                    // Apply 'like' filter on product name only if $search is not null
                    return $query->where('name', 'like', '%' . $search . '%');
                })->when($category_id, function ($query, $category_id) {
                    // Apply 'like' filter on product name only if $search is not null
                    return $query->where('cat_id',$category_id);
                })->get()->toArray();
        /*//print_r($productResult);exit();
        return ProductResource::collection(
            Product::where('restaurant_type', $restaurant_type)
                ->when($search, function ($query, $search) {
                    // Apply 'like' filter on product name only if $search is not null
                    return $query->where('name', 'like', '%' . $search . '%');
                })
                ->get()
        );*/


        return response()->json(["code"=>200,'message' => 'Get product successfully',"data"=>$productResult]);

    }
    
    public function getProductList(Request $request)
    {
        $restaurant_type = $request->restaurant_type;
        $search = $request->product_name;
        
        return ProductResource::collection(
            Product::where('restaurant_type', $restaurant_type)
                ->when($search, function ($query, $search) {
                    // Apply 'like' filter on product name only if $search is not null
                    return $query->where('name', 'like', '%' . $search . '%');
                })
                ->get()
        );

    }
    

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
        //    'image' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'unit' => 'required|string|max:10',
        ]);


        if ($validator->fails()) {
            return response()->json(["message"=>"The name has already been taken.","code"=>500], 422);
        }


         $exists = Product::where('name', $request->name)
        ->where('cat_id', $request->cat_id)
        ->exists();

        if ($exists) {
            return response()->json(["code"=>500,'message' => 'The product name already exists in this category.',"status"=>false]);
        }


        $data = $request->all(); // Copy request data
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
                            "selling_price"=>!empty($request->all()["ml_selling_price"][$ml])?$request->all()["ml_selling_price"][$ml]:0,
                            "mrp_price"=>!empty($request->all()["ml_price"][$ml])?$request->all()["ml_price"][$ml]:0,
                            "quanity"=>!empty($request->all()["ml_quanity"][$ml])?$request->all()["ml_quanity"][$ml]:0,
                        ];
            }

            if (!empty($create_price)) {
                    CounterProductStock::insert($create_price);
            }
        }
        
        return response()->json(["code"=>200,'message' => 'Product created successfully']);
    }

    public function show($id)
    {
        $product = Product::findOrFail($id);
        return new ProductResource($product);
    }

    public function update(Request $request, $id)
    {
        // Check if a product with the same category_id and code exists (excluding the current product)
        $duplicate = Product::where('cat_id', $request->cat_id)
                            ->where('name', $request->name)
                            ->where('id', '!=', $id)
                            ->first();

        if ($duplicate) {
            // If a duplicate record is found, return an error response
            return response()->json(['message' => 'Duplicate product found with the same category_id and code.',"code"=>500], 400);
        }

        // Find the product by id
        $product = Product::findOrFail($id);

        // Handle base64 image upload if provided
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

                    // Store file path in the request data (this will be saved in the product update)
                    $request->merge(['image' => $filePath]);
                }
            }
        } else {
            // If no image is provided, don't update the image field
            $request->merge(['image' => $product->image]); // Keep the existing image
        }

        // Update the product with the request data
        $product->update($request->all());


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
                            "selling_price"=>!empty($request->all()["ml_selling_price"][$ml])?$request->all()["ml_selling_price"][$ml]:0,
                            "mrp_price"=>!empty($request->all()["ml_price"][$ml])?$request->all()["ml_price"][$ml]:0,
                            "quanity"=>!empty($request->all()["ml_quanity"][$ml])?$request->all()["ml_quanity"][$ml]:0,
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


    public function destroy($id){
        
        $product = Product::findOrFail($id);
        $product->delete();
        CounterProductStock::where('product_id', $id)->delete();
        ProductMlPrice::where('product_id', $id)->delete();
        return response()->json(['message' => 'Product deleted successfully']);

    }

    public function getProductsByCategoryId($id,$restaurant_type="1",$food_type="1",$name=''){

        $category = Category::where('id', $id)->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        
        if($food_type==1){
            $foodType="veg";
        }else {
            $foodType="nonveg";
        }
        
        $query = Product::where('cat_id', $id)
                            ->when(!empty($name), function ($q) use ($name) {
                                $q->where('name', 'LIKE', "%{$name}%");
                            })
                            ->with(['product_prices' => function ($q) {
                                $q->where('quanity', '>', 0); // load only available stock
                            }]);

                        // Apply extra condition only for bar/restaurant type = 2
                        if ($restaurant_type == 2) {
                            $query->whereHas('product_prices', function ($q) {
                                $q->whereNotIn('ml_size', [30, 60]) // exclude peg sizes
                                  ->where('quanity', '>', 0);
                            });
                        }

        $products = $query->get();
        return response()->json(["data"=>$products,'message' => 'Get Product successfully']);
    

    }
    
    
    public function getOrderProducts($id,$restaurant_type="1",$food_type="1",$name=''){

        $category = Category::where('id', $id)->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        
        if($food_type==1){
            $foodType="veg";
        }else {
            $foodType="nonveg";
        }
        
        /*if($restaurant_type==1){
            $products = Product::where('cat_id', $id)->where('restaurant_type',$restaurant_type)->where('food_type',$foodType)->get();
        }else {
            $products = Product::where('cat_id', $id)->where('restaurant_type',$restaurant_type)->get();
        }*/
        
        $products = Product::when($foodType && $restaurant_type == 1, function($query) use ($foodType) {
        return $query->where('food_type', $foodType);
    })
    ->when(!empty($name), function($query) use ($name) {
        return $query->where('name', 'LIKE', '%'.$name.'%');
    })
    ->get();

        
        


        return response()->json(["data"=>$products,'message' => 'Get Product successfully']);
    }

    public function getProductById($id)
    {
        $product = Product::where('id', $id)->first(); // Use first() instead of get()

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


    public function getProductsUnitByCatId($id,$restaurant_type)
    {

        $category = Category::where('id', $id)->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        $productUnits = Product::select('id', 'unit','name')->where('restaurant_type',$restaurant_type)->get();


        return response()->json(["data"=>$productUnits,'message' => 'Get Product unit successfully']);
    }

     public function addToCart(Request $request)
     {
            $userId = 1; // default user
    
            $item = $request->item; // single item
            if(!empty($item["selected_product"])) {
    
                $item["price"]=$item["selected_product"]["selling_price"];
                $item["product_ml_price_id"]=$item["selected_product"]["id"];
                $item["ml_size"]=$item["selected_product"]["ml_size"];
                
                //Check if product already exists for this table
                $cartItem = Cart::where('user_id', $userId)
                ->where('table_id', $item['table_id'])
                ->where('product_id', $item['product_id'])
                ->where('product_ml_price_id', $item['product_ml_price_id'])
                ->where('category_id', $item['category_id'])
                ->first();
            }else {
                        // Check if product already exists for this table
                $cartItem = Cart::where('user_id', $userId)
                ->where('table_id', $item['table_id'])
                ->where('product_id', $item['product_id'])
                ->where('category_id', $item['category_id'])
                ->first();
            }
            
            if ($cartItem) {
                // Update quantity
                if($item["add_status"]==1) {
                    $cartItem->quantity += 1;
                }else {
                    
                    if($cartItem->quantity==1 || $cartItem->quantity < 1) {
                        
                        return response()->json([
                            'message' => 'Can not descrease quantity',
                            'code' => 500,
                        ], 200);
                        
                        
                    }
                    $cartItem->quantity -= 1;
                }
                $cartItem->total_price= $cartItem->quantity*$cartItem->price;
                $cartItem->save();
            } else {
                // Create new cart item
                Cart::create([
                    'user_id' => $userId,
                    'table_id' => $item['table_id'],
                    'name' => $item['name'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['qty'],
                    'price' => $item['price'],
                    'category_id'=>$item['category_id'],
                    'restaurant_type' => $item['restaurant_type'],
                    'total_price' => ($item['price']*$item['qty']),
                    'product_ml_price_id' => !empty($item['product_ml_price_id'])?$item['product_ml_price_id']:null,
                ]);
            }
    
            return response()->json([
                'message' => 'Cart updated successfully',
                'code' => 200,
            ], 200);
    }
    
    public function getCartItem(Request $request)
     {
            $userId = 1; // default user
    
            $table_id = $request->table_id; // single item
            
            // Check if product already exists for this table
            $cartItem = Cart::with(['product:id,name',"selected_product"])->where('user_id', $userId)
                ->where('table_id', $table_id)->get();
    
            return response()->json([
                "data"=>$cartItem,
                'message' => 'Cart item',
            ], 200);
    }
    
    
    public function removeCartItem(Request $request)
     {
         $record_id = $request->record_id;
         $deleted = Cart::where('id', $record_id)->delete();
        if ($deleted) {
            return response()->json(['message' => 'Item deleted successfully'], 200);
        } else {
            return response()->json(['message' => 'Item not found'], 404);
        }

    }
    
    public function removeAllCartItem(Request $request)
     {
         $table_id=$request->all()['tableId'];
         $deleted = Cart::where('table_id', $table_id)->delete();
        if ($deleted) {
            return response()->json(['message' => 'Item deleted successfully'], 200);
        } else {
            return response()->json(['message' => 'Item not found'], 404);
        }

    }
    
    public function saveOrderRecord(Request $request){
         
         $table_id=$request->all()['table_id'];
          DB::beginTransaction();

        try {
        // Calculate total price
        $subtotal = collect($request->items)->sum(function ($item) {
            return $item['price'] * $item['qty'];
        });

        
        
        
         //$tax = round($subtotal * 0.06, 2);
         $tax = 0; 
        // Step 3️⃣: Final total (subtotal + tax)
        $total = $subtotal + $tax;

        $discountAmount=0;
        $discountType  = $request->discount_type ?? null;
        $discountValue = (float) ($request->discount ?? 0);

        $discountAmount = 0;

        if ($discountType === 'percentage') {
            $discountValue = min(max($discountValue, 0), 100);
            $discountAmount = ($subtotal * $discountValue) / 100;
        }
        elseif ($discountType === 'flat') {
            $discountAmount = min($discountValue, $subtotal);
        }

        $total = $subtotal - $discountAmount;


        //Create Order record
        if(!empty($request->partialAmount)) {
            
            if($request->partialAmount > $total) {
                
                return response()->json([
                            'message' => 'Partial Amount should not grater than total amount',
                        ], 201);exit;
                
            }
            
            $order = Order::create([
                'table_id' => $request->table_id,
                'user_id' => $request->user_id,
                'total_price' => ($total-$request->partialAmount),
                'payment_method' => $request->payment_method,
                'partial_amount' => $request->partialAmount,
                'total_amount' => $total,
                "tax"=>$tax,
                'status' => 0,
                "offline_status"=>!empty($request->offline_status)?$request->offline_status:0,
                "uuid"=>$request->uuid,
                "discount"=>$discountValue,
                "discount_amount"=>$discountAmount,
                "discount_type"=>$discountType
            ]);
        }else {
            
            $order = Order::create([
                'table_id' => $request->table_id,
                'user_id' => $request->user_id,
                'total_price' => $total,
                'payment_method' => $request->payment_method,
                "tax"=>$tax,
                'status' => 0,
                "offline_status"=>!empty($request->offline_status)?$request->offline_status:0,
                "uuid"=>$request->uuid,
                "discount"=>$discountValue,
                "discount_amount"=>$discountAmount,
                "discount_type"=>$discountType
            ]);
            
        }

        // Insert Order Items
        foreach ($request->items as $item) {

            OrderItem::create([
                'order_id' => $order->id,
                'table_id' => $request->table_id,
                'category_id' => $item['category_id'],
                'user_id' => $request->user_id,
                'product_id' => $item['product_id'],
                'price' => $item['price'],
                'ml_size' => !empty($item["selected_product"]['ml_size'])?$item["selected_product"]['ml_size']:0,
                'quantity' => $item['qty'],
                'total_price' => $item['price'] * $item['qty'],
                'status' => 0,
                "offline_status"=>!empty($request->offline_status)?$request->offline_status:0,
                "uuid"=>$request->uuid
            ]);
            
            if(!empty($item["selected_product"])) {


                $ml = (int) $item["selected_product"]['ml_size'];

                if (in_array($ml, [30, 60])) {

                    //🔹Get current loose row (opened bottle)
                    $looseRow = ProductMlPrice::where('product_id', $item['product_id'])
                        ->whereNotIn('ml_size', [30, 60])
                        ->where('loose_remaining_ml', '>', 0)
                        ->orderBy('id', 'desc')
                        ->first();


                    // 🔹 If no loose row OR insufficient ml → open new bottle
                    if (!$looseRow || $looseRow->loose_remaining_ml < $ml) {

                        $NewBottle = ProductMlPrice::where('product_id', $item['product_id'])
                            ->whereNotIn('ml_size', [30, 60])   // 750ml
                            ->where('quanity', '>', 0)
                            ->orderBy('id', 'desc')
                            ->first();


                     
                        if (!$NewBottle || $NewBottle->quanity <= 0) {
                            return response()->json([
                            'message' => 'Stock not available'
                        ], 201);
                        }

                        // open bottle
                        $NewBottle->quanity -= 1;
                        $NewBottle->loose_remaining_ml += $NewBottle->ml_size; // usually 750
                        $NewBottle->save();

                        // now this becomes the active loose row
                        $looseRow = $NewBottle;
                    }

                    // 🔹 Deduct loose ml
                    $looseRow->loose_remaining_ml -= $ml;
                    $looseRow->save();

                } else {

                    // 🔹 Direct bottle sale (90 / 180 / 750)
                    $productRec = ProductMlPrice::where([
                        'product_id' => $item['product_id'],
                        'ml_size'    => $ml
                    ])->first();

                    if (!$productRec ||  $productRec->quanity <= 0) {

                       return response()->json([
                            'message' => 'Stock not available'
                        ], 201);
                    }

                    ProductMlPrice::where('id', $productRec->id)
                        ->update(['quanity' => $productRec->quanity - 1]);
                }

                /*$productRec = ProductMlPrice::where([
                                        'product_id'=>$item['product_id'],
                                        'ml_size'=>$item["selected_product"]['ml_size']
                                    ])->firstOrFail();
                ProductMlPrice::where('id', $productRec->id)->update(['quanity' => $productRec->quanity-1]);*/
            }
            
        }
        $deleted = Cart::where('table_id', $table_id)->delete();
        DB::commit();
        
        return response()->json([
                            'message' => 'Order placed successfully',
                            'order_id' => $order->id,
                            'total_price' => $total
                        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Failed to place order',
            'error' => $e->getMessage()
        ], 500);
    }

    }
    
    /*Created date: 01-01-2026*/

    public function synchroniseLocalOrderRecord(Request $request){

        if (!Order::where('offline_status',1)->exists()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No record for synchronise',
                ], 200);
        }
        $records = Order::with('order_item')
                            ->where('offline_status', 1)
                            ->get()->toArray();
        try {
            $response = Http::withHeaders([
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ])->post('https://www.koltegroup.in/backend/api/products/saveOfflineOrderRecord', [
                            'offline_item' => $records
                        ]);

            if ($response->failed()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Server reachable but API failed',
                    'error'   => $response->body()
                ], 500);
            }
            foreach($records as $row) {
                Order::where('id', $row["id"])->update(['offline_status' =>0]);
            }
            return response()->json([
                'status' => true,
                'data'   => $response->json()
            ]);
        } catch (ConnectionException $e) {
                //🔴Internet / DNS / Network gone
                return response()->json([
                            'status'  => false,
                            'message' => 'No internet connection. Please check your network.'
                        ], 201);
        }
    }

    public function saveOfflineOrderRecord(Request $request){

            DB::beginTransaction();
            try {

                //Create Order record
                
                foreach($request->offline_item as $order_item) {
                    
                    $order = Order::create([
                                    'table_id' => $order_item["table_id"],
                                    'user_id' => $order_item["user_id"],
                                    'total_price' => $order_item["total_price"],
                                    'payment_method'=>$order_item["payment_method"],
                                    "tax"=>$order_item["tax"],
                                    'status' => 0,
                                    "offline_status"=>2
                                ]);
                    //Insert Order Items
                    foreach ($order_item["order_item"] as $item) {

                        OrderItem::create([
                            'order_id' => $item["order_id"],
                            'table_id' => $item["table_id"],
                            'category_id' => $item["category_id"],
                            'user_id' => $item["user_id"],
                            'product_id' => $item["product_id"],
                            'price' => $item["price"],
                            'ml_size' => $item["ml_size"],
                            'quantity' => $item["quantity"],
                            'total_price' => $item["total_price"],
                            'status' => 0,
                            "offline_status"=>2
                        ]);
                    }
                }

                DB::commit();
                return response()->json(['message' => 'Product synchronise successfully'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to place order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
  
  /*END*/



    public function syncToLive(){


        // Check if no pending or failed orders exist
        if (!Order::whereIn('sync_status', ['pending', 'failed'])->exists()) {
            return response()->json([
                'status'  => true,
                'message' => 'All local products are already synchronized successfully!',
            ], 200);
        }

        // Fetch pending or failed orders with items + product
        $orders = Order::with(['order_item.product:id,name,restaurant_type'])
            ->whereIn('sync_status', ['pending', 'failed'])
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();


        $failedOrder=[];                        


        foreach ($orders as $order) {

            $response = Http::withHeaders([
                                        'Content-Type' => 'application/json',
                                        'Accept' => 'application/json',
                                    ])->post('https://www.koltegroup.in/backend/api/products/sync', [
                                        'offline_item' => $order
                                    ]);


            if ($response->successful()) {
                Order::where('id', $order["id"])->update(['sync_status' =>'synced']);
            }

            if ($response->status() === 409) {
                
                $order["fail_reason"]=$response->json('reason');
                Order::where('id', $order["id"])->update(['sync_status' =>'failed',"fail_reason"=>$response->json('reason')]);
                $failedOrder[]=$order;
                
                /*return response()->json([
                    'status'  => false,
                    'message' => $response->json(),
                ], 409);*/
                
            }
        }

        if(!empty($failedOrder)) {    
                
                return response()->json([
                    'status'  => false,
                    'failedOrder'  => $failedOrder,
                    'message' =>'Something went wrong! Some records might not be synchronized.',
                ], 200);

        }else {

            return response()->json([
                    'status'  => true,
                    'message' => 'local records synchronized successfully with Live.',
                ], 200);


        }
    }


   public function sync(Request $request){

        DB::beginTransaction();

        try {
            
            // prevent duplicate
            if (Order::where('uuid', $request->uuid)->exists()) {
                return response()->json(['status' => 'synced'],200);
            }

            $offline_item = $request->offline_item;
            
            foreach ($offline_item["order_item"] as $order_item) {
                
                if($order_item["product"]["restaurant_type"]==2) {

                    if (in_array((int) $order_item['ml_size'], [30, 60], true)) {
                        
                        $ml=(int) $order_item['ml_size'];
                         //🔹Get current loose row (opened bottle)
                        $looseRow = ProductMlPrice::where('product_id', $order_item['product_id'])
                                                    ->whereNotIn('ml_size', [30, 60])
                                                    ->where('loose_remaining_ml', '>', 0)
                                                    ->orderBy('id', 'desc')
                                                    ->first();
                        //🔹If no loose row OR insufficient ml → open new bottle
                        if (!$looseRow || $looseRow->loose_remaining_ml < $ml) {

                            $NewBottle = ProductMlPrice::where('product_id', $order_item['product_id'])
                                ->whereNotIn('ml_size', [30, 60])   // 750ml
                                ->where('quanity', '>', 0)
                                ->orderBy('id', 'desc')
                                ->first();
                                
                                if (!$NewBottle || $NewBottle->quanity <= 0) {
                                        return response()->json([
                                        'message' => 'Stock not available',
                                        'reason' => 'Stock not available',
                                    ], 409);
                            }
                            
                            //open bottle
                            
                            $NewBottle->quanity -= 1;
                            $NewBottle->loose_remaining_ml += $NewBottle->ml_size; // usually 750
                            $NewBottle->save();
                            //now this becomes the active loose row
                            $looseRow = $NewBottle;
                        
                        }
                        // 🔹 Deduct loose ml
                        $looseRow->loose_remaining_ml -= $ml;
                        $looseRow->save();

                    }else {

                        //🔹Direct bottle sale (90 / 180 / 750)
                        $productRec = ProductMlPrice::where([
                                            'product_id' => $order_item['product_id'],
                                            'ml_size'    => $order_item['ml_size']
                                        ])->first();

                        if (!$productRec ||  $productRec->quanity <= 0) {

                           return response()->json([
                                'message' => 'Stock not available',
                                'reason' => 'Stock not available'
                            ], 409);

                        }
                    }
                }

            }

            $orderItem =$request->offline_item;

            $order = Order::create([
                                    'table_id' => $orderItem["table_id"],
                                    'uuid' => $orderItem["uuid"],
                                    'user_id' => $orderItem["user_id"],
                                    'total_price' => $orderItem["total_price"],
                                    'payment_method'=>$orderItem["payment_method"],
                                    "tax"=>$orderItem["tax"],
                                    'status' => 0,
                                    "offline_status"=>2
                                ]);
                                
            $orderId = $order->id;                    
            //Insert Order Items
            foreach ($orderItem["order_item"] as $item) {

                OrderItem::create([
                    'order_id' => $orderId,
                    'uuid' => $item["uuid"],
                    'table_id' => $item["table_id"],
                    'category_id' => $item["category_id"],
                    'user_id' => $item["user_id"],
                    'product_id' => $item["product_id"],
                    'price' => $item["price"],
                    'ml_size' => $item["ml_size"],
                    'quantity' => $item["quantity"],
                    'total_price' => $item["total_price"],
                    'status' => 0,
                    "offline_status"=>2
                ]);

            }

            DB::commit();
            return response()->json(['status' => 'synced'],200);

        } catch (\Exception $e) {
            DB::rollBack();
              return response()->json([
                'message' => 'Failed to place order',
                'reason' => 'Failed to place order',
                'error' => $e->getMessage()
            ], 409);
        }
    }


}
