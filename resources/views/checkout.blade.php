@extends('layouts.app')
@section('title', 'Our Products')
@section('content')
    <style>
        body {
            background-color: #f8f9fa;
        }
        .cart-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            clear: both;
        }
        .cart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cart-item img {
            width: 80px;
            height: 80px;
            border-radius: 5px;
        }
        .quantity-control {
            display: flex;
            align-items: center;
        }
        .quantity-control button {
            border: none;
            background: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            margin: 0 5px;
        }
        .checkout-btn {
            background: #28a745 !important;
            color: white;
            font-size: 18px;
            padding: 10px;
        }
        .header_section{
            float: none !important;
            margin-bottom: 15px;
        }

        @media (max-width: 575px) {
  .login_text {
    display: block !important; 
  }
}
    </style>
    <div>
     
     <div style="float:right;">
         <button id="printBtn">🖨️ Print Invoice</button>
        <div id="invoiceContent" style="display:none;"></div>
     </div>   

        
    <div class="cart-container">
        <!-- Header -->

        <div class="cart-header mb-3">
            <button class="btn btn-outline-secondary" onclick="goBack()">←</button>
            <h4 class="m-0">Your Cart</h4>
            <button class="btn btn-outline-danger">✖</button>
        </div>
        <?php $totalGRandTotal=0;?>
         @foreach($products as $product)
           <?php $totalGRandTotal +=$product["price"]*$product['quantity'];?>
        <!-- Cart Item -->
        <div class="cart-item d-flex align-items-center mb-3">
            <?php 
            if($product["restaurant_type"]==1) {
            $product['image']= !empty($product['image'])?$product['image']:"img-7.png";
            }else {
                
                $product['image']= !empty($product['image'])?$product['image']:"kitchenjpg.jpg";
                
                
            }
            
            ?>
            <img src="{{ url('asset/images/' . $product['image']) }}" alt="Product">
            <div class="ms-3 flex-grow-1">
                <h5>{{$product["product"]["name"]}}</h5>
               <!--  <p class="text-muted m-0">Wine</p> -->
                <p class="text-primary fw-bold m-0">Rs. <span class="price_text_total" id="price_text_total_{{$product['id']}}">{{$product["price"]*$product['quantity']}}</span></p>
            </div>
            <div class="quantity-control">
                <button class="btn btn-sm updateQty" data-cartId="{{$product['id']}}" data-table_id="{{$product['table_id']}}" data-product_id="{{$product['product_id']}}" data-price="{{$product['price']}}" data-updateStauts="0">-</button>
                <span class="fw-bold" id="cart_{{$product['id']}}">{{$product['quantity']}}</span>
                <button class="btn btn-sm updateQty" data-cartId="{{$product['id']}}" data-table_id="{{$product['table_id']}}" data-product_id="{{$product['product_id']}}" data-price="{{$product['price']}}" data-updateStauts="1">+</button>
            </div>
        </div>
        @endforeach

        <!-- Payment Method -->
        <div class="mb-3">
            <label class="form-label">Payment Method:</label>
            <select class="form-select" name="payment_method" id="payment_method">
                <option value="cod">Cash on Delivery</option>
                <option value="card">Credit/Debit Card</option>
            </select>
        </div>

        <!-- Subtotal -->
        <div class="d-flex justify-content-between fw-bold mb-3">
            <span>Subtotal</span>
            <span>Rs. <span class="Subtotal">{{$totalGRandTotal;}}</span></span>
        </div>

        <!-- Checkout Button -->
        <button class="btn checkout-btn w-100">Proceed to Checkout</button>
    </div>

    </div>
    <!-- Bootstrap JS -->
    <script>
$(document).ready(function() {
 
    $(".updateQty").click(function() {
          
        let cartId = $(this).data("cartid"); 
        let updateStatus = $(this).data("updatestauts"); 

        let table_id = $(this).data("table_id"); 
        let productId = $(this).data("product_id"); 
        let price = $(this).data("price"); 
        user_id = "{{ Auth::id() }}"; // Get authenticated user ID

        if(updateStatus) {
            urlUpdate="https://restaurant.keninfotec.com/api/rest_tables/addToCart";
        }else {            

            totalQty = $("#cart_"+cartId).text();
            if(parseInt(totalQty)-parseInt(1)==0) {

                alert("quantity should not be less than 1"); return
                return false;
            }
            urlUpdate="https://restaurant.keninfotec.com/api/rest_tables/updateQtyCart";
        }

 

        $.ajax({
            url: urlUpdate,
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                table_id: table_id,
                category_id:parseInt(2),
                user_id: user_id,
                product_id: productId,
                price: price,
                quantity: 1
            }),
            success: function (response) {
                console.log(response);

                if(updateStatus) {
                    totalQty = $("#cart_"+cartId).text();
                    totalQty = parseInt(totalQty)+parseInt(1);
                    $("#cart_"+cartId).text(totalQty);

                    $("#price_text_total_"+cartId).text(totalQty*price);
                    
                     alert("Item added to cart successfully!");
           
                }else {            
                    totalQty = $("#cart_"+cartId).text();
                    totalQty =parseInt(totalQty)-parseInt(1);
                    $("#cart_"+cartId).text(totalQty);


                    $("#price_text_total_"+cartId).text(totalQty*price);

                    alert("Item remove to cart successfully!");
           
                }

                 let totalPrice = 0;
                $(".price_text_total").each(function() {
                    let price = parseFloat($(this).text().trim()); // Extract and convert price
                    if (!isNaN(price)) {
                        totalPrice += price;
                    }
                });
                $(".Subtotal").text(totalPrice);
            },
            error: function (xhr, status, error) {
                alert(xhr.responseJSON ? xhr.responseJSON.message : "An error occurred");
            }
        });
    });

})

$(document).ready(function() {

    $(".checkout-btn").click(function() {
        table_id = "{{$table_id}}";
        user_id = "{{ Auth::id() }}"; // Get authenticated user ID

        payment_method = $("#payment_method").val();
        $.ajax({
            url: "https://restaurant.keninfotec.com/api/rest_tables/checkout",
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                table_id: table_id,
                user_id: user_id,
                payment_method: payment_method
            }),
            success: function (response) {
                console.log(response);

                window.location.href = "{{ url('/dashboard') }}";

            },
            error: function (xhr, status, error) {
                alert(xhr.responseJSON ? xhr.responseJSON.message : "An error occurred");
            }
        });


    })
})
function goBack() {
    window.history.back();
}
    $(document).ready(function () {
        $('#printBtn').on('click', function () {
            $.ajax({
                url: "{{ url('/invoice/data') }}",   // ✅ use Blade helper
                type: "POST",
                data: {
                    table_id: "{{ $table_id }}",                // ✅ safe blade variable
                    _token: "{{ csrf_token() }}"                // ✅ CSRF token required
                },
                success: function (data) {
                    // Insert HTML into hidden div
                    $('#invoiceContent').html(data);

                    // Open print window
                    var printWindow = window.open('', '', 'width=800,height=600');
                    printWindow.document.write('<html><head><title>Invoice</title></head><body>');
                    printWindow.document.write($('#invoiceContent').html());
                    printWindow.document.write('</body></html>');
                    printWindow.document.close();
                    printWindow.print();
                },
                error: function (xhr, status, error) {
                    console.error("Print request failed:", error);
                }
            });
        });
    });
</script>


@endsection