@extends('layouts.app')
@section('title', 'Inventory')
@section('content')
<div class="wrap" style="margin:92px 60px !important">
  <div class="card">
    <div id="summary" class="summary" style="margin:10px;">
    <div class="stat"><div class="muted">Items</div><strong><?=count($products);?></strong></div>
    <?php if($restaurant_type==2) { ?>
        @php $totalQty = 0; @endphp
        @foreach($products as $product_row)
            @foreach($product_row["product_prices"] as $product)
                @php $totalQty += $product["quanity"]; @endphp
            @endforeach
        @endforeach
    <div class="stat"><div class="muted">Total Units</div><strong>{{$totalQty}}</strong></div>
    <?php } ?>
    <div class="summary-actions">
       <button class="btn btn-success" id="show_form">➕ Add Counter</button>
    </div>
  </div>
    <table id="inventoryTable">
      <thead>
        <tr>
          <th>SKU</th>
          <th>Name</th>
          <th style="text-align:center;">Price</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($products as $product)
        <?php $product['image']= !empty($product['image'])?$product['image']:"img-7.png";?>
            <tr>
                <td>P-{{ $product["id"] }}</td>
                <td>{{ $product["name"] }}</td>
                <td> @if(!empty($product['product_prices']))
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                @foreach($product['product_prices'] as $price)
                    <span style="border:1px solid #ccc; padding:4px 8px; border-radius:4px;">
                        {{ $price['ml_size'] }}ml -
                        ₹{{ $price['selling_price'] }}
                        (Qty: {{ $price['quanity'] }})
                    </span>
                @endforeach
            </div>
            @else
            @endif
            </td>
            </tr>
        @endforeach
    </tbody>
    </table>
    <div id="noResults" class="no-results" style="display:none">No results found</div>
  </div>
</div>
<!--Modal-->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">Add stock to counter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" id="close_btn">X</button>
            </div>
            <div class="modal-body">
                <form id="addForm" class=""  method="post" enctype="multipart/form-data" action="{{ route('products.add-counter') }}">
                     <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                   <!-- Restaurant Type -->
                    <!-- <div class="modal-body" id="resultQuantity"></div> -->
                    <div class="row" id="resultContainer"></div>
                    <div class="row" id="resultQuantity"></div>
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

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).ready(function () {
        
        var categoryFilter = $("#categoryFilter").val();
        //If value is blank or undefined, set to first valid option
        
        if (!categoryFilter || categoryFilter === "") {
            var firstOption = $("#categoryFilter option:not([value='']):first").val();
            if (firstOption) {
                let restTypeId = "{{ $restaurant_type }}"; // Get authenticated user ID
                window.location = "{{ url('/inventory') }}/"+firstOption+"/"+restTypeId;
                //$("#categoryFilter").val(firstOption).trigger("change");
            }
        }
        console.log("Selected category:", $("#categoryFilter").val());
    });

    function getProductByCat(catId){
        let restTypeId = "{{ $restaurant_type }}"; // Get authenticated user ID
        window.location = "{{ url('/inventory') }}/"+catId+"/"+restTypeId;
    }

    function getProductByfoodType(food_type){
        categoryFilter = $("#categoryFilter").val();
        let restTypeId = "{{ $restaurant_type }}"; // Get authenticated user ID
        window.location = "{{ url('/inventory') }}/"+categoryFilter+"/"+restTypeId+"/"+food_type;
    }

    $(document).ready(function () {

        cat_id ="{{$cat_id}}";
        user_id = "{{ Auth::id() }}"; // Get authenticated user ID
        $(".searchByCat").click(function () {
            restType = $(this).val();
            window.location = "{{ url('/inventory') }}/"+cat_id+"/"+restType;
        });

    });
  
   $(document).ready(function () {

    const BASE_URL = "{{ url('/') }}"; 
    $("#show_form").on("click", function () {

        let product_id = $("#product_id").val();
        let urlGiven = `${BASE_URL}/products/addCounterFromStock`;
        $.ajax({
            url: urlGiven,
            type: "POST",
            data: { test: "dfdf" },
            success: function (response) {
                // response contains HTML
                $("#resultContainer").html(response);
                $("#addModal").modal("show");
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message ?? "Error");
            }
        });
        //$("#addModal").modal("show");

    });

    $("#close_btn").on("click", function () {
        $("#addForm")[0].reset(); // Reset the form
        $("#addModal").modal("hide");
    });
    
$(document).ready(function () {

    $("#addForm").validate({
        ignore: [],
        rules: {
            select_product: {
                required: true
            }
        },
        messages: {
            select_product: {
                required: "Please select a product"
            }
        },
        highlight: function (element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid');
        },
        //THIS IS REQUIRED
        submitHandler: function (form) {

            // Option 1: Normal form submit
            // form.submit();
            // Option 2: AJAX submit (recommended)
            let formData = new FormData(form);
            $.ajax({
                url: $(form).attr('action'),
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    console.log(response);
                    alert("Form submitted successfully");
                    window.location.href=window.location.href;
                },
                error: function (xhr) {
                    alert(xhr.responseJSON?.message ?? "Error");
                }
            });
            return false; // ❗ Prevent default submit

        }
    });

});


    //Dynamically attach validation to qty fields
    $('.qty-input').each(function () {
        $(this).rules("add", {
            required: true,
            digits: true,
            min: 1,
            maxAvailable: true
        });
    });

});


$(document).ready(function () {
    $('#inventoryTable').DataTable({
        "pageLength": 10,
        "lengthMenu": [5, 10, 25, 50, 100],
        "ordering": true,
        "searching": true
    });
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

    .summary-actions {
      margin-left: auto;
    }

</style>

@endsection
