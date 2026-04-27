<div class="col-md-12">
<?php 
    $srNo=0;
 foreach ($products["available_product_prices"] as $row) { $srNo++; ?>
    <div class="row mt-3">
        
        <div class="col-md-3">
            <label><strong><small>MRP {{ $row['mrp_price'] }} Rs ({{ $row['ml_size'] }} ML)</small></strong></label>
            <input readonly class="form-control" value="{{ $row['mrp_price'] }}" name="mrp_price[{{$row['id']}}]">
            <input type="hidden" class="form-control" value="{{ $row['ml_size'] }}" name="ml_size[{{$row['id']}}]">
        </div>

        <div class="col-md-3">
            <label><strong><small>Selling Price {{ $row['selling_price'] }} Rs  ({{ $row['ml_size'] }} ML)</small></strong></label>
            <input readonly class="form-control" value="{{ $row['selling_price'] }}" name="selling_price[{{$row['id']}}]">
        </div>

        <!-- MAIN STOCK -->
        <div class="col-md-2">
            <label><strong><small>Available Qty</small></strong></label>
            <input readonly
                   type="number"
                   class="form-control main-qty"
                   data-id="{{ $row['id'] }}"
                   value="{{ $row['quanity'] }}" name="quanity[{{$row['id']}}]">
        </div>

        <!-- ENTER QTY -->
        <div class="col-md-4">
            <label><strong><small>Enter Qty</small></strong></label>
            <input type="number"
                   min="0"
                   class="form-control input-qty"
                   data-id="{{ $row['id'] }}" name="new_quanity[{{$row['id']}}]">
            <small class="text-danger d-none" id="err_{{ $row['id'] }}" >
                Quantity cannot exceed available stock
            </small>
        </div>

    </div>
<?php } ?>
<?php if($srNo==0) { ?>
<div class="row mt-3" > <div class="col-md-12" style="text-align:center;"><strong>No Stock Found!</strong></div></div>
<?php } ?>
</div>


<script>
document.querySelectorAll('.input-qty').forEach(input => {

    input.addEventListener('input', function () {

        let id = this.dataset.id;
        let enteredQty = parseInt(this.value || 0);

        let mainQtyInput = document.querySelector(
            '.main-qty[data-id="'+id+'"]'
        );

        let errorEl = document.getElementById('err_' + id);

        let originalQty = parseInt(mainQtyInput.getAttribute('data-original')) || parseInt(mainQtyInput.value);

        // store original qty once
        mainQtyInput.setAttribute('data-original', originalQty);

        let remaining = originalQty - enteredQty;

        if (remaining < 0) {
            errorEl.classList.remove('d-none');
            this.value = '';
            mainQtyInput.value = originalQty;
        } else {
            errorEl.classList.add('d-none');
            mainQtyInput.value = remaining;
        }
    });

});
</script>
