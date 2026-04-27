@extends('layouts.app')
@section('title', 'Our Products')
@section('content')

<style type="text/css">
    
        @media (max-width: 575px) {
          .login_text {
            display: block !important; 
          }
}

.login_text {
            display: block !important; 
          }

</style>
<div class="product_section layout_padding">
    <div class="container">
         <div style="text-align: center;padding-bottom: 20px;">
          <input type="radio" value="1" name="searchByCat"  {{ $cat_id == 1 ? 'checked' : '' }} class="searchByCat"> Bar  

        <input type="radio" value="2" name="searchByCat"  {{ $cat_id == 2 ? 'checked' : '' }} class="searchByCat"> Kitech 
</div>
<div class="row">
        <div class="col-md-6"></div>

    <!--<div class="col-md-6">
        <input type="text" value="" name="search_by_name" class="search_by_name form-control" placeholder="Search By Product"  >
    </div>-->
</div>
<div id="loadDefaultHtml">
        <div class="row" >
            @foreach($products as $product)
            <?php $product['image']= !empty($product['image'])?$product['image']:"img-7.png";?>
                <div class="col-lg-3 col-sm-6">
                    <div class="product_box">
                        <h4 class="bursh_text">{{ $product["name"] }}</h4>
                        <p class="lorem_text">{{ $product["description"] }}</p>
                        <img src="{{ url('asset/images/' . $product['image']) }}" class="image_1">
                        <h3 class="price_text" style="color:#000; width:100%; text-align:center;">Price: Rs.{{ $product["price"] }}</h3>
                        <a href="javascript:void(0);" 
                           class="btn btn-primary add-to-cart" 
                           data-product-id="{{ $product['id'] }}" 
                           data-price="{{ $product['price'] }}" 
                           style="width:100%">Add To Cart</a>
                    </div>
                </div>
            @endforeach
        </div>
        <?php if(empty($products)) { ?>
         <div class="row">
                <div class="col-lg-12 col-sm-12">
                    <p class="product_text" style="text-align: center;"><strong>No product found</strong></p>
                </div>
        </div>
        <?php } ?>

    </div>

    </div>
</div>

<!-- jQuery Script for AJAX Call -->
<script>
    $(document).ready(function () {

        table_id ="{{$table_id}}";
        cat_id ="{{$cat_id}}";
        user_id = "{{ Auth::id() }}"; // Get authenticated user ID

        $(".searchByCat").click(function () {
            restType = $(this).val();
            window.location = "{{ url('/products') }}/"+table_id+"/"+restType;
        });

        $(".search_by_name").on("keyup",function() {

            loadDefaultHtml($(this).val());
        })

       $(document).on("click", ".add-to-cart", function () {
            let productId = $(this).data("product-id");
            let price = $(this).data("price");

            let table_id = "{{$table_id}}"; // Assuming stored in session
              

            $.ajax({
                    url: "https://restaurant.keninfotec.com/api/rest_tables/addToCart",
                    type: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({
                        table_id: table_id,
                        category_id:parseInt(2),
                        user_id: user_id,
                        product_id: productId,
                        price: price,
                        quantity: 1,
                        restaurant_type:cat_id
                    }),
                    success: function (response) {
                        cartCount = $(".cart-count").text().trim(); 
                        cartCountTotal =parseInt(cartCount)+parseInt(1);
                        $(".cart-count").text(cartCountTotal);
                        alert("Item added to cart successfully!");
                    },
                    error: function (xhr, status, error) {
                        alert(xhr.responseJSON ? xhr.responseJSON.message : "An error occurred");
                    }
            });
        });

        loadDefaultHtml();
    });

    function loadDefaultHtml(product_name='') {
    const BASE_URL = "{{ url('/') }}";
    const cat_id = "{{ $cat_id }}";
/*
   $.ajax({
        url: BASE_URL + '/api/products/getProductList',
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        type: "POST",
        data: {
            cat_id: cat_id,
            product_name:product_name
        },
        success: function (response) {
            $("#loadDefaultHtml").html(response);
        },
        error: function (xhr, status, error) {
            alert(xhr.responseJSON ? xhr.responseJSON.message : "An error occurred");
        }
    });*/
}


</script>
@endsection
