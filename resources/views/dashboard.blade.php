@extends('layouts.app')
@section('title', 'Table')
@section('content')

<style>
    .table-card {
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }

    .table-card.occupied {
        background-color: #f8f0ff;
        color: #cccccc;
    }

    .table-card.available {
        background-color: #ffffff;
        color: #343a40;
    }

    .table-card h5 {
        margin-bottom: 5px;
        font-weight: bold;
    }

    .table-card .price {
        font-size: 18px;
        font-weight: bold;
        color: #ffbb00;
    }

    .add-table-btn {
        border-radius: 50px;
        padding: 10px 20px;
        font-size: 15px;
    }

    .header_section {
        float: none !important;
        margin-bottom: 15px;
    }
</style>

<div class="container mt-4" style="margin:80px 0px 0px 50px !important">
    <!-- Add Table Button placed above table boxes -->
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-success " id="show_form">➕ Add Table</button>
    </div>

    <div class="row">
        @foreach ($tables as $table)
            <div class="col-md-4">
                <div class="table-card {{ $table['total_price_sum'] > 0 ? 'occupied' : 'available' }}">
                    <a href="{{route('products.list', ['table_id' => $table['id'], 'cat_id' => 1]) }}">
                        <h5>{{ $table['name'] }}</h5>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">Create Table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" id="close_btn" aria-label="Close">X</button>
            </div>
            <div class="modal-body">
                <form id="addForm">
                    <div class="mb-3">
                        <label for="table_number" class="form-label">Table Number</label>
                        <input type="text" class="form-control" id="table_number" name="table_number" required>
                    </div>
                    <button type="submit" class="btn btn-success">Submit</button>
                </form>
                <div id="responseMessage" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Show modal on button click
    $("#show_form").on('click', function() {
        $("#addModal").modal('show');
    });

    // Close modal and clear input
    $("#close_btn").on('click', function() {
        $("#table_number").val('');
        $("#addModal").modal('hide');
    });
     const BASE_URL = "{{ url('/') }}";

    // jQuery Validation and AJAX Form Submission
    $("#addForm").validate({
        rules: {
            table_number: {
                required: true,
                minlength: 1
            }
        },
        messages: {
            table_number: {
                required: "Please enter a table number",
                minlength: "Table number must have at least 1 digit"
            }
        },
        submitHandler: function(form) {
            let table_number = $("#table_number").val();
            $.ajax({
                url: BASE_URL+"/api/rest_tables/create_table",
                type: "POST",
                contentType: "application/json",
                data: JSON.stringify({ name: table_number }),
                success: function(response) {
                    if (response.status == 200) {
                        alert("Table added successfully!");
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON ? xhr.responseJSON.message : "An error occurred");
                }
            });
            return false; // Prevent default form submission
        }
    });
});
</script>

@endsection
