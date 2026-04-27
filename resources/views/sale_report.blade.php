@extends('layouts.app')
@section('title', 'Sale report')
@section('content')

<?php $restaurant_type = 1; $categories=[];?>
 <!-- jQuery (FIRST) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<!-- DataTables JS (AFTER jQuery) -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<!-- jQuery UI (for datepicker) -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>      
   $(function () {
    $("#from_date, #to_date").datepicker({
        dateFormat: "dd-mm-yy"
    });
});
</script>

<div class="wrap" style="margin:92px 60px !important">
  <div class="card">
         
        <div class="row">
            <div class="col-lg-3"><input id="from_date" type="text" placeholder="Start date" /></div>
            <div class="col-lg-3"> <input id="to_date" type="text" placeholder="To date" /></div>
            <div class="col-lg-3">
                <select class="form-select form-control" name="categoryFilter" id="categoryFilter" style="min-width: 180px;">
                    <option value="">Select Product</option>
                    @foreach($products as $key=>$cat)
                      <option value="{{ $cat }}" >
                        {{ $cat }}
                      </option>
                    @endforeach
                  </select> 
        </div>
        <?php //if($restaurant_type==1) { ?>
                    
        <div class="col-lg-3">
          <select class="form-select form-control" name="food_type" id="paymentMethodFilter" style="min-width: 180px;">
            <option value="">Select Payment Type</option>
            <option value="cash">
                Cash
            </option>
            <option value="online">
                Online
            </option>

            <option value="Partial" >
               Partial Payment
            </option>
        </select> 

        </div>
        <?php //} ?>
        <div class="col-lg-3">
            <div class="controls" style="display: flex; !important;">
              <button id="export">Export CSV</button>
            </div>
        </div>

    </div>

    <div id="summary" class="summary" style="margin:10px;">
        <div class="stat"><div class="muted">Items</div><strong><?=count($orders);?></strong></div>
        <!--<div class="stat">
            <div class="muted">Total Units</div><strong>{{ 1 }}</strong>
        </div>-->
            
        <div class="stat">
    <div class="muted">Total Sales</div>
    <strong>{{ !empty($orders['total_sales'])?$orders['total_sales']:0}} Rs</strong>
</div>


        
    </div>
<div class="table-responsive">
    <table id="inventoryTable">
      <thead>
        <tr>
          <th>SKU</th>
          <th style="text-align:center;">Table</th>
          <th style="text-align:center;">Product name</th>
          <th style="text-align:center;">Total Amount</th>
          <th style="text-align:center;">Partial Amount</th>
          <th style="text-align:center;">Total Paid</th>
          <th style="text-align:center;">Size</th>
          <th style="text-align:center;">Quantity</th>
          <th style="text-align:center">Payment method</th>
          <th style="text-align:center">Date</th>
          <th style="text-align:center">Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @forelse($orders ?? [] as $order)
            <?php //echo "<pre>";print_r($order->toArray());?>
            <?php $order_item=[];
                $quantity=[];
                $ml_size=[];
                foreach($order->order_item as $item) {
                    //echo "<pre>";print_r($item->toArray());
                    if($item->ml_size!=0) {
                        $ml_size[]=$item->ml_size;
                    }
                    $order_item[] =$item["product"]["name"];
                    $quantity[] =$item->quantity;
                }

                $quantity_sum = array_sum($quantity);
                $order_item = array_unique($order_item);
            ?>

            <tr>
                <td>P-{{ $order["id"] }}</td>
                <td style="text-align:center;">{{ strtoupper($order['rest_table']["name"]) }}</td>
                <td style="text-align:center;"><?php echo implode(",", $order_item);?></td>
                <td style="text-align:center;">{{ number_format($order['total_price'], 2) }}</td>
                <td style="text-align:center;">{{ number_format($order['partial_amount'], 2) }}</td>
                <td style="text-align:center;">
                    {{ number_format($order['total_amount'], 2) }}
                </td>
                <td style="text-align:center;"><?php echo implode(",", $ml_size);?></td>
                <td style="text-align:center;">{{$quantity_sum}}</td>
                <td style="text-align:center;">{{ucfirst($order["payment_method"])}}</td>
                <td style="text-align:center;">{{date("d-m-Y",strtotime($order["created_at"]))}}</td>


                <td style="text-align:center"> <a href="javascript:void(0);" 
                           class="btn btn-primary add-to-cart" 
                           data-product-id="{{ $order['id'] }}" 
                           data-price="{{ $order['id'] }}" 
                           style="width:100%; background: #343a40" onclick="editProduct('{{ $order["id"] }}')">View Items ({{count($order["order_item"])}})</a>
                </td>

            </tr>
        @endforeach
    </tbody>
    </table>
</div>
    <div id="noResults" class="no-results" style="display:none">No results found</div>
  </div>
</div>
   
<!-- jQuery Script for AJAX Call -->
<script>
  

    function getProductByCat(catId){
        let restTypeId = "{{ 1}}"; // Get authenticated user ID
        window.location = "{{ url('/inventory') }}/"+catId+"/"+restTypeId;
    }

    function getProductByfoodType(food_type){
        categoryFilter = $("#categoryFilter").val();
        let restTypeId = "{{ 1 }}"; // Get authenticated user ID
        window.location = "{{ url('/inventory') }}/"+categoryFilter+"/"+restTypeId+"/"+food_type;
    }

    $(document).ready(function () {

        cat_id ="{{1}}";
        user_id = "{{ Auth::id() }}"; // Get authenticated user ID

        $(".searchByCat").click(function () {
            restType = $(this).val();
            window.location = "{{ url('/inventory') }}/"+cat_id+"/"+restType;
        });
    });
  
   $(document).ready(function () {
    $("#show_form").on("click", function () {
        $("#addModal").modal("show");
    });

    $("#close_btn").on("click", function () {
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
            product_id =$("#product_id").val();
            if(product_id!=="") {

                formData = {
                    product_id:product_id,
                    food_type:$("#food_type").val(),
                cat_id:$("#cat_id").val(),
                restaurant_type: $("select[name='restaurant_type']").val(),
                name: $("#name").val(),
                description: $("#description").val(),
                price: $("#price").val(),
                mrp_price: $("#mrp_price").val(),
                quantity: $("#quantity").val(),
                unit: $("#unit").val()
            };
                    urlGiven="https://restaurant.keninfotec.com/api/products/update/"+product_id;
            }else {
                 formData = {
                cat_id:$("#cat_id").val(),
                food_type:$("#food_type").val(),
                restaurant_type: $("select[name='restaurant_type']").val(),
                name: $("#name").val(),
                description: $("#description").val(),
                price: $("#price").val(),
                mrp_price: $("#mrp_price").val(),
                quantity: $("#quantity").val(),
                unit: $("#unit").val()
            };
               urlGiven= "https://restaurant.keninfotec.com/api/products/create";
            }

            $.ajax({
                url: urlGiven,
                type: "POST",
                contentType: "application/json",
                data: JSON.stringify(formData),
                success: function (response) {
                    console.log("Response:", response);

                    if (response.status == 200) {
                        alert("Product added successfully!!");
                        console.log("Reloading page...");
                        window.location.reload();
                    } else {
                        alert(response.message);
                        window.location.reload();

                    }
                },
                error: function (xhr, status, error) {
                    alert(xhr.responseJSON ? xhr.responseJSON.message : "An error occurred");
                }
            });
            return false; // Prevent default form submission
        }
    });
});

function editProduct(productId) {
    const BASE_URL = "{{ url('/') }}";
     const TOKEN = "{{ auth()->user()->api_token ?? '' }}"; 
    $.ajax({
        url: BASE_URL+"/getProductItemById/" + productId,
        type: "GET",
        headers: {
        "Authorization": "Bearer " + TOKEN,
        "Accept": "application/json"
    },
        success: function (response) {
            $(".modal-body").html(response);
            
                $("#addModal").modal("show");   
        },
        error: function (xhr) {
            alert(xhr.responseJSON ? xhr.responseJSON.message : "An error occurred");
        }
    });
}
$(document).ready(function () {
    
    let table = $('#inventoryTable').DataTable({
    order: [[0, 'desc']]   // Default order
});
    // Filter by Payment Method (column index 5)
    $('#paymentMethodFilter').on('change', function () {
        var value = $(this).val();
         table
            .column(6) // ✅ Product name column index
            .search(value ? value : '', true, false)
            .draw();
    });


    $('#categoryFilter').on('change', function () {
        var value = $(this).val();
        table
            .column(2) // ✅ Product name column index
            .search(value ? value : '', true, false)
            .draw();
    });

    $('#restaurant_type_id').on('change', function () {
        var value = $(this).val();
        table
            .column(9) // ✅ Product name column index
            .search(value ? value : '', true, false)
            .draw();
    })


});

    
document.getElementById("export").addEventListener("click", function () {

    let table = $('#inventoryTable').DataTable();
    let data = table.rows({ search: 'applied' }).data();  // filtered rows

    console.log(data)

    let csv = "SKU,Table,Item name,Total Payment,Partial amount, Total Paid,ML Size,Quantity, Payment Method\n";

    for (let i = 0; i < data.length; i++) {
        let row = data[i];

        // Strip HTML using jQuery
        let sku          = $("<div>").html(row[0]).text().trim();
        let table_name      = $("<div>").html(row[1]).text().trim();
        let item_name        = $("<div>").html(row[2]).text().trim();
        let total_payment    = $("<div>").html(row[3]).text().trim();
        let partial_amount          = $("<div>").html(row[4]).text().trim();
        let total_paid          = $("<div>").html(row[5]).text().trim();
        let Size          = $("<div>").html(row[6]).text().trim();   // Removes <h2>
        let quantity          = $("<div>").html(row[7]).text().trim();   // Removes <h2>
        let method= $("<div>").html(row[8]).text().trim();   // Removes <h2>
        console.log("method",method)

        csv += `"${sku}","${table_name}","${item_name}","${total_payment}","${partial_amount}","${total_paid}","${Size}","${quantity}","${method}"\n`;
    }

    let blob = new Blob([csv], { type: "text/csv" });
    let url = window.URL.createObjectURL(blob);

    let a = document.createElement("a");
    a.href = url;
    a.download = "inventory.csv";
    a.click();
    window.URL.revokeObjectURL(url);
});



$.fn.dataTable.ext.search.push(function (settings, data) {

    var min = $('#from_date').val();
    var max = $('#to_date').val();
    var date = data[7]; // Date column index (27-12-2025)

    if (!date) return true;

    // convert dd-mm-yyyy → yyyy-mm-dd
    function parseDate(d) {
        var parts = d.split('-');
        return new Date(parts[2], parts[1] - 1, parts[0]);
    }

    var rowDate = parseDate(date);
    var minDate = min ? parseDate(min) : null;
    var maxDate = max ? parseDate(max) : null;

    if (
        (!minDate && !maxDate) ||
        (!minDate && rowDate <= maxDate) ||
        (minDate <= rowDate && !maxDate) ||
        (minDate <= rowDate && rowDate <= maxDate)
    ) {
        return true;
    }
    return false;
});


</script> 
<!-- Modal -->
<!-- Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">Products</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" id="close_btn">X</button>
            </div>
            <div class="modal-body">
                <form id="addForm" class="form-grid">
                   <!-- Restaurant Type -->
                    <div class="mb-3">
                        <label class="form-label">Restaurant Type</label>
                        <select class="form-control" name="restaurant_type">
                            <option value="1" <?= $restaurant_type==1 ? "selected" : "" ?>>Restaurant</option>
                            <option value="2" <?= $restaurant_type==2 ? "selected" : "" ?>>Bar</option>
                        </select>
                    </div>
                    <!--Food Type-->
                    <?php if(1==1) { ?>
                    <div class="mb-3">
                        <label class="form-label">Food Type</label>
                        <select class="form-control" name="food_type" id="food_type">
                            <option value="veg">Veg</option>
                            <option value="nonveg">Non-Veg</option>
                        </select>
                    </div>
                    <?php } ?>
                    <!-- Category -->
                    <div class="mb-3">
                        <label class="form-label">Select Category</label>
                        <select class="form-control" name="cat_id" id="cat_id">
                            <?php foreach($categories as $cat) { ?>
                            <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <!--Name-->
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <!--Description (full-width)-->
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" id="description" colspan="" placeholder="Description"></textarea>

                    </div>
                    <!--Price-->
                    <div class="price-row">
                        <div class="mb-3">
                            <label class="form-label">Selling Price</label>
                            <input type="text" class="form-control" id="price" name="price" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mrp Price</label>
                            <input type="text" class="form-control" id="mrp_price" name="mrp_price" required>
                        </div>
                    </div>

                    <!-- Quantity -->
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="text" class="form-control" id="quantity" name="quantity" required>
                    </div>

                    <!-- Unit -->
                    <div class="mb-3">
                        <label class="form-label">Unit</label>
                        <input type="text" class="form-control" id="unit" name="unit" required>
                    </div>

                    <!-- Hidden -->
                    <input type="hidden" id="product_id" name="product_id">

                    <!-- Submit (full row) -->
                    <div class="full-row">
                        <button type="submit" class="btn btn-success w-100">Submit</button>
                    </div>
                </form>

                <div id="responseMessage" class="mt-3"></div>
            </div>

        </div>
    </div>
</div>
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
        max-height: calc(90vh - 120px);
        padding-right: 10px;
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
<script>
    
    $.fn.dataTable.ext.search.push(function (settings, data) {
    let min = $('#from_date').val();
    let max = $('#to_date').val();
    let date = data[7]; // 👈 Date column index (check your table)

    if (!date) return true;

    function parseDate(d) {
        let parts = d.split('-');
        return new Date(parts[2], parts[1] - 1, parts[0]);
    }

    let rowDate = parseDate(date);
    let minDate = min ? parseDate(min) : null;
    let maxDate = max ? parseDate(max) : null;

    if (
        (!minDate && !maxDate) ||
        (!minDate && rowDate <= maxDate) ||
        (minDate <= rowDate && !maxDate) ||
        (minDate <= rowDate && rowDate <= maxDate)
    ) {
        return true;
    }
    return false;
});


$('#from_date, #to_date').on('change', function () {
    $('#inventoryTable').DataTable().draw();
});

</script>

<style>
    .wrap {
    width: 100% !important;
    margin-top: 30px  !important;
}

.dataTables_wrapper {
    width: 100% !important;
}

.table-responsive {
    overflow-x: auto;
}

</style>
@endsection
