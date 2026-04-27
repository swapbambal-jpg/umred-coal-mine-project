<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\RestTable;

use Illuminate\Support\Facades\DB;

class TableController extends Controller
{
    public function index()
    {
        $response = Http::get('https://restaurant.keninfotec.com/api/rest_tables');
        return $response->json();
    }

    public function list()
    {
        $cartList = "";
         $data = RestTable::select(
                        'rest_tables.id',
                        'rest_tables.name',
                        DB::raw('(SELECT id FROM carts WHERE carts.table_id = rest_tables.id LIMIT 1) as cart_id'),
                        DB::raw('(SELECT SUM(total_price) FROM carts WHERE carts.table_id = rest_tables.id) as total_price_sum')
                    )
                    ->get()
                    ->map(function ($table) {
                        // Format sum
                        $table->total_price_sum = number_format((float) $table->total_price_sum, 2, '.', '');
                        return $table;
                    })
                    ->toArray();
                    
        return view('dashboard', ['tables' => $data,"cartList"=>$cartList]);
        
    }
}
