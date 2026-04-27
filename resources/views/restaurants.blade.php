@extends('layouts.app')
@section('title', 'Restaurant')
@section('content')
<div class="wrap" style="margin:92px 60px !important">
  <div class="card">

    <div id="summary" class="summary" style="margin:10px;">
    <div class="stat"><div class="muted">Items</div><strong><?=count($products);?></strong></div>
    <div class="summary-actions">
        <div class="row">
             <div class="col-md-3">
            
            <select class="form-select form-control" name="categoryFilter" id="categoryFilter" onchange="getProductByCat(this.value)" style="min-width: 180px;">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
              <option value="{{ $cat['id'] }}" {{ isset($cat_id) && $cat_id == $cat['id'] ? 'selected' : '' }}>
                {{ $cat['name'] }}
              </option>
            @endforeach
          </select>
      </div>
             <div class="col-md-3">
                <select class="form-select form-control" name="food_type" id="food_type"
                    onchange="getProductByfoodType(this.value)" style="min-width: 180px;">
                    <option value="">Select Food Type</option>
                    <option value="veg" {{ isset($food_type) && $food_type == 'veg' ? 'selected' : '' }}>
                        Veg
                    </option>
                    <option value="nonveg" {{ isset($food_type) && $food_type == 'nonveg' ? 'selected' : '' }}>
                        Non Veg
                    </option>
                </select>


            </div>
            <div class="col-md-3">
                <button class="btn btn-success" id="export">Export CSV</button>
            </div>
            <div class="col-md-3"><button class="btn btn-success" id="show_form" style="float: right;">➕ Add Product</button> </div>
            
        </div>
     </div>

    <?php if($restaurant_type==2) { ?>
        @php $totalQty = 0; @endphp
        @foreach($products as $product)
            @php $totalQty += $product->quantity; @endphp
        @endforeach
    <div class="stat"><div class="muted">Total Units</div><strong>{{$totalQty}}</strong></div>
    <?php } ?>
  
  </div>

    <table id="inventoryTable">
      <thead>
        <tr>
          <th>SKU</th>
          <th>Product</th>
          <th>MRP Price</th>
          <th >Selling price</th>
           <?php if($restaurant_type==2) { ?>
            <th style="text-align:center;">Quantity</th>
           <?php }?>
          <th style="text-align:right">Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($products as $product)

        <?php $product['image']= !empty($product['image'])?$product['image']:"img-7.png";?>
            <tr>
                <td>P-{{ $product["id"] }}</td>
                <td>{{ $product["name"] }}</td>
                <td>{{ $product["mrp_price"] }}</td>
                <td>{{ $product["price"] }}</td>
                <?php if($restaurant_type==2) { ?>
                <td>
                    <?php if($product["quantity"]==0) { ?>
                        <h2 class="price_text" style="color:#000; width:100%; text-align:center; color: red;">Out of stock</h2>
                    <?php } else { ?>
                        <h2 class="price_text" style="color:#000; width:100%; text-align:center;">{{ $product["quantity"] }}
                        </h2>
                    <?php } ?>
                </td>
                    <?php } ?>
                
              <td style="text-align:right"> <a href="javascript:void(0);" 
                           class="btn btn-primary add-to-cart" 
                           data-product-id="{{ $product['id'] }}" 
                           data-price="{{ $product['price'] }}" 
                           style="width:100%; background: #343a40" onclick="editProduct('{{ $product["id"] }}')">Edit</a></td>
            </tr>
        @endforeach
    </tbody>
    </table>

    <div id="noResults" class="no-results" style="display:none">No results found</div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" id="close_btn">X</button>
            </div>
            <div class="modal-body">
                <form id="addForm" class=""  method="post" enctype="multipart/form-data">
                     <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                   <!-- Restaurant Type -->

                    <div class="row">
                        <div class="col-md-6">
                            <input type="file" name="image">
                        </div>

                        <div class="col-md-6">
                            <img src="" id="image_src" style="width:50px; height: 50px;">
                        </div>
                    </div>
                    <div class="row">

                        <div class="col-md-6">
                        <label class="form-label">Restaurant Type</label>
                        <select class="form-control" name="restaurant_type">
                            <?php if($restaurant_type==1) { ?>
                             <option value="1" <?= $restaurant_type==1 ? "selected" : "" ?>>Restaurant</option>
                             <?php } if($restaurant_type==2) { ?>
                             <option value="2" <?= $restaurant_type==2 ? "selected" : "" ?>>Bar</option>
                            <?php } ?>
                        </select>
                    </div>
                    <!--Food Type-->
                    <?php if($restaurant_type==1) { ?>
                    <div class="col-md-6">
                        <label class="form-label">Food Type</label>
                        <select class="form-control" name="food_type" id="food_type">
                            <option value="veg">Veg</option>
                            <option value="nonveg">Non-Veg</option>
                        </select>
                    </div>
                    <?php } ?>
                    <!-- Category -->
                    <div class="col-md-6">
                        <label class="form-label">Select Category</label>
                        <select class="form-control" name="cat_id" id="cat_id">
                            <?php foreach($categories as $cat) { ?>
                            <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    </div>
                    <!--Price-->
                    <?php if($restaurant_type==1) { ?>
                    
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-6">
                            <label class="form-label">Mrp Price</label>
                            <input type="text" class="form-control" id="mrp_price" name="mrp_price" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selling Price</label>
                            <input type="text" class="form-control" id="price" name="price" required>
                        </div>
                        
                        <!-- <div class="col-md-4">
                            <label class="form-label">Quantity</label>
                            <input type="text" class="form-control" id="quantity" name="quantity" required>
                        </div> -->
                    </div>
                    <?php } ?>
                    <?php if($restaurant_type==2) { 

                      $liquir_size=[30,60,90,180,200,330,375,650,750];

                        ?>
                     <?php foreach($liquir_size as $row) { ?>   
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-4">
                            <label class="form-label">Mrp Price (<small>{{$row}} ML</small>)</label>
                            <input type="text" class="form-control" id="ml_price_{{$row}}" name="ml_price[{{$row}}]" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Selling Price (<small>{{$row}} ML</small>)</label>
                            <input type="text" class="form-control" id="ml_selling_price_{{$row}}" name="ml_selling_price[{{$row}}]" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Quantity</label>
                            <input type="text" class="form-control" id="ml_quanity_{{$row}}" name="ml_quanity[{{$row}}]" required>
                        </div>
                    </div>
                    <?php } ?>

                    <!-- <div class="row" style="margin-top: 20px;">
                        <div class="col-md-4">
                            <label class="form-label">Mrp Price (<small>180 ML</small>)</label>
                            <input type="text" class="form-control" id="ml_price_180" name="ml_price[180]" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Selling Price (<small>180 ML</small>)</label>
                            <input type="text" class="form-control" id="ml_selling_price_180" name="ml_selling_price[180]" required>
                        </div>
                        

                        <div class="col-md-4">
                            <label class="form-label">Quantity</label>
                            <input type="text" class="form-control" id="ml_quanity_180" name="ml_quanity[180]" required>
                        </div>

                    </div> -->

                    <!-- <div class="row" style="margin-top: 20px;">
                        <div class="col-md-4">
                            <label class="form-label">Mrp Price (<small>750 ML</small>)</label>
                            <input type="text" class="form-control" id="ml_price_750" name="ml_price[750]" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Selling Price (<small>750 ML</small>)</label>
                            <input type="text" class="form-control" id="ml_selling_price_750" name="ml_selling_price[750]" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quantity</label>
                            <input type="text" class="form-control" id="ml_quanity_750" name="ml_quanity[750]" required>
                        </div>

                    </div> -->

                    <?php } ?>

                    <div class="row">


                    <!--Description (full-width)-->
                    <div class="col-md-6">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" id="description" colspan="" placeholder="Description"></textarea>

                    </div>
                        
                    </div>

                    <!-- Hidden -->
                    <input type="hidden" id="product_id" name="product_id">

                    <!-- Submit (full row) -->
                    <div class="row" style="margin-top:20px;">
                        <div class="col-md-12">
                                <button type="submit" class="btn btn-success w-100">Submit</button>
                        </div>
                    </div>
                </form>

                <div id="responseMessage" class="mt-3"></div>
            </div>

        </div>
    </div>
</div>

<!-- jQuery Script for AJAX Call -->
<script>
    $(document).ready(function () {
        var categoryFilter = $("#categoryFilter").val();

        // If value is blank or undefined, set to first valid option
        if (!categoryFilter || categoryFilter === "") {
            var firstOption = $("#categoryFilter option:not([value='']):first").val();
            if (firstOption) {

            let restTypeId = "{{ $restaurant_type }}"; // Get authenticated user ID
            window.location = "{{ url('/restaurants') }}/"+firstOption+"/"+restTypeId;
              // $("#categoryFilter").val(firstOption).trigger("change");
            }
        }

        console.log("Selected category:", $("#categoryFilter").val());
    });

    function getProductByCat(catId){
        let restTypeId = "{{ $restaurant_type }}"; // Get authenticated user ID
        window.location = "{{ url('/restaurants') }}/"+catId+"/"+restTypeId;
    }

    function getProductByfoodType(food_type){
        categoryFilter = $("#categoryFilter").val();
        let restTypeId = "{{ $restaurant_type }}"; // Get authenticated user ID
        window.location = "{{ url('/restaurants') }}/"+categoryFilter+"/"+restTypeId+"/"+food_type;
    }

    $(document).ready(function () {

        cat_id ="{{$cat_id}}";
        user_id = "{{ Auth::id() }}"; // Get authenticated user ID

        $(".searchByCat").click(function () {
            restType = $(this).val();
            window.location = "{{ url('/restaurants') }}/"+cat_id+"/"+restType;
        });
    });
  
   $(document).ready(function () {
    $("#show_form").on("click", function () {
        $("#addModal").modal("show");
    });

    $("#close_btn").on("click", function () {
        $("#addForm")[0].reset(); // Reset the form
        $("#addModal").modal("hide");
    });

    // jQuery Validation
    $("#addForm").validate({
        rules: {
            restaurant_type: {
                required: true
            },
            name: {
                required: true,
                minlength: 2
            },
            description: {
                required: true,
                minlength: 5
            },
            price: {
                required: true,
                number: true,
                min: 0
            },
            mrp_price: {
                required: true,
                number: true,
                min: 0
            },
            quantity: {
                required: true,
                digits: true,
                min: 1
            },
            unit: {
                required: true,
                minlength: 1
            }
        },
        messages: {
            restaurant_type: {
                required: "Please select a restaurant type"
            },
            name: {
                required: "Please enter the name",
                minlength: "Name must be at least 2 characters long"
            },
            description: {
                required: "Please enter a description",
                minlength: "Description must be at least 5 characters long"
            },
            price: {
                required: "Please enter a price",
                number: "Please enter a valid number",
                min: "Price must be a positive value"
            },
            mrp_price: {
                required: "Please enter a price",
                number: "Please enter a valid number",
                min: "Price must be a positive value"
            },
            quantity: {
                required: "Please enter the quantity",
                digits: "Only whole numbers are allowed",
                min: "Quantity must be at least 1"
            },
            unit: {
                required: "Please enter the unit",
                minlength: "Unit must be at least 1 character long"
            }
        },
        submitHandler: function (form) {
            //AJAX Form Submission
              const BASE_URL = "{{ url('/') }}"; 

            let product_id = $("#product_id").val();
            let urlGiven = product_id 
                            ? `${BASE_URL}/products/update/${product_id}`
                            : `${BASE_URL}/products/create`;
            var formData = new FormData(document.getElementById("addForm"));

            $.ajax({
                url: urlGiven,
                type: "POST",
                data: formData,
                contentType: false,      // ❗ Important
                processData: false,      // ❗ Important
                cache: false,
                success: function (response) {
                    console.log(response);
                    window.location.href=window.location.href;
                },
                error: function (xhr) {
                    alert(xhr.responseJSON?.message ?? "Error");
                }
            });


            return false; // Prevent default form submission  
        }
    });
});

function editProduct(productId) {
    const BASE_URL = "{{ url('/') }}"; 
    let urlGiven = `${BASE_URL}/products/getProductById/${productId}`;
    $.ajax({
        url: urlGiven,
        type: "GET",
         headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
        },
        contentType: "application/json",
        success: function (response) {
            if (response.code === 200) {
                let product = response.data;
                // Set product ID (for updating)
                $("#product_id").val(product.id);
                // Fill form fields with product data
                $("#addForm select[name='restaurant_type']").val(product.restaurant_type);
                $("#name").val(product.name);
                $("#description").val(product.description);
                $("#price").val(product.price);
                $("#mrp_price").val(product.mrp_price);
                $("#quantity").val(product.quantity);
                $("#unit").val(product.unit);
                

                var liquir_size = [30, 60, 90, 180, 200, 330, 375, 650, 750];

                for (let Ml of liquir_size) {
                    $("#ml_selling_price_" + Ml).val(product["ml_selling_" + Ml]);
                    $("#ml_price_" + Ml).val(product["ml_price_" + Ml]);
                    $("#ml_quanity_" + Ml).val(product["ml_qty_" + Ml]);
                }


                $("#ml_selling_price_180").val(product.ml_selling_180);
                $("#ml_price_180").val(product.ml_price_180);
                $("#ml_quanity_180").val(product.ml_qty_180);

                $("#ml_selling_price_750").val(product.ml_selling_750);
                $("#ml_price_750").val(product.ml_price_750);
                $("#ml_quanity_750").val(product.ml_qty_750);
                

                $("#image_src").attr("src", product.image ? BASE_URL+"/public/products/images/" + product.image : "/no-image.png");



                


                // Show the modal
                $("#addModal").modal("show");
            } else {
                alert(response.message);
            }
        },
        error: function (xhr) {
            alert(xhr.responseJSON ? xhr.responseJSON.message : "An error occurred");
        }
    });
}

$(document).ready(function () {
    $('#inventoryTable').DataTable({
        "pageLength": 10,
        "lengthMenu": [5, 10, 25, 50, 100],
        "ordering": true,
        "searching": true
    });
});

document.getElementById("export").addEventListener("click", function () {

    let table = $('#inventoryTable').DataTable();
    let data = table.rows({ search: 'applied' }).data();  // filtered rows

    let csv = "SKU,Product,Price,Selling Price,Quantity\n";

    for (let i = 0; i < data.length; i++) {
        let row = data[i];

        // Strip HTML using jQuery
        let sku          = $("<div>").html(row[0]).text().trim();
        let product      = $("<div>").html(row[1]).text().trim();
        let price        = $("<div>").html(row[2]).text().trim();
        let sellPrice    = $("<div>").html(row[3]).text().trim();
        let qty          = $("<div>").html(row[4]).text().trim();   // Removes <h2>

        csv += `"${sku}","${product}","${price}","${sellPrice}","${qty}"\n`;
    }

    let blob = new Blob([csv], { type: "text/csv" });
    let url = window.URL.createObjectURL(blob);

    let a = document.createElement("a");
    a.href = url;
    a.download = "inventory.csv";
    a.click();
    window.URL.revokeObjectURL(url);
});
</script> 

<style>


.wrap {
    display: flex;
    justify-content: center;
    margin-top: 30px;
}

.card {
    width: 100%;
    max-width: 1100px;
    background: #ffffff;
    border-radius: 14px;
    padding: 25px 30px;
    box-shadow: 0px 4px 18px rgba(0,0,0,0.12);
    border: 1px solid #eee;
}

.card h1 {
    font-size: 26px;
    font-weight: 700;
    color: #333;
    margin-bottom: 5px;
}

.card .sub {
    color: #666;
    margin-bottom: 20px;
    font-size: 14px;
}

/* Controls Row */
.controls {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.control label {
    font-size: 13px;
    margin-bottom: 4px;
    display: block;
    color: #444;
}

.control input,
.control select {
    width: 100%;
    padding: 7px 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

/* Buttons */
.controls button {
    padding: 8px 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-weight: 600;
}

#reset {
    background: #eee;
}
#export {
    background: #28a745;
    color: #fff;
}

/* Summary Stats */
.summary {
    display: flex;
    gap: 25px;
    margin-bottom: 20px;
}

.stat {
    background: #f6f6f6;
    padding: 12px 15px;
    border-radius: 8px;
    text-align: center;
    min-width: 110px;
}

.muted {
    font-size: 12px;
    color: #888;
}

.stat strong {
    font-size: 18px;
    color: #333;
}

/* Table Style */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}

table thead {
    background: #f0f0f0;
}

table th, table td {
    padding: 10px 12px;
    border-bottom: 1px solid #ddd;
    font-size: 14px;
}

table tr:hover {
    background: #fafafa;
}

/* No results */
.no-results {
    text-align: center;
    padding: 15px;
    background: #ffe8e8;
    color: #c00;
    border-radius: 8px;
    margin-top: 10px;
}

/* RESPONSIVE */
@media (max-width: 991px) {
    .controls {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .controls {
        grid-template-columns: 1fr;
    }
    .summary {
        flex-direction: column;
    }
    .card {
        padding: 20px;
    }
}

    .price-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
    .modal-dialog {
        max-width: 800px; /* wider for 2 columns */
    }
    .modal-content {
        max-height: 90vh;
    }
    .modal-body {
        overflow-y: auto;
        /*max-height: calc(90vh - 120px);
        padding-right: 10px;*/
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr; /* Two equal columns */
        gap: 15px; /* spacing */
    }
    /* full width rows */
    .full-row {
        grid-column: span 2;
    }
    .error{
        color: red !important;
    }
</style>

@endsection
