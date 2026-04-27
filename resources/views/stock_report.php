@extends('layouts.app')
@section('title', 'Our Products')
@section('content')

<div class="product_section layout_padding">
    <div class="container">
         <div style="text-align: center;padding-bottom: 20px;">
          <input type="radio" value="1" name="searchByCat" 
    {{ $cat_id == 1 ? 'checked' : '' }} class="searchByCat"> Bar  

<input type="radio" value="2" name="searchByCat" 
    {{ $cat_id == 2 ? 'checked' : '' }} class="searchByCat"> Kitech
</div>

        <div class="row" id="loadHtml">
            @foreach($products as $product)
            <?php $product['image']= !empty($product['image'])?$product['image']:"img-7.png";?>
                <div class="col-lg-3 col-sm-6">
                    <div class="product_box">
                        <h4 class="bursh_text">{{ $product["name"] }}</h4>
                        <p class="lorem_text">{{ $product["description"] }}</p>
                        <img src="{{ url('asset/images/' . $product['image']) }}" class="image_1">
                        <?php if($product["quantity"]==0) { ?>
                            <h2 class="price_text" style="color:#000; width:100%; text-align:center; color: red;">Quantity: Out of stock</h2>
                        <?php } else { ?>
                            <h2 class="price_text" style="color:#000; width:100%; text-align:center;">Quantity: {{ $product["quantity"] }}</h2>
                        <?php } ?>
                        <h3 class="price_text" style="color:#000; width:100%; text-align:center;">Price: Rs.{{ $product["price"] }}</h3>
                        <a href="javascript:void(0);" 
                           class="btn btn-primary add-to-cart" 
                           data-product-id="{{ $product['id'] }}" 
                           data-price="{{ $product['price'] }}" 
                           style="width:100%">Edit</a>
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

<!-- jQuery Script for AJAX Call -->
<script>
    $(document).ready(function () {

        table_id =2;
        cat_id ="{{$cat_id}}";
        user_id = "{{ Auth::id() }}"; // Get authenticated user ID

        $(".searchByCat").click(function () {
            restType = $(this).val();
            window.location = "{{ url('/inventory') }}/"+table_id+"/"+restType;
        });


     
    });

</script>
@endsection
