<div class="row" id="loadDefaultHtml">
        @foreach($products as $product)
            <?php 
            if($cat_id==1) {
             $product['image']= !empty($product['image'])?$product['image']:"img-7.png";
            }else {
                $product['image']= !empty($product['image'])?$product['image']:"kitchenjpg.jpg";
                
            }
            ?>
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


