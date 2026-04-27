<div class="col-md-6">
    <select class="form-control" onchange="getProductByAttributes(this.value)" id="select_product" name="select_product">
        <option value="">Select Product</option>
        <?php foreach($products as $row) {?>
        <option value="<?=$row["id"];?>"><?=$row["name"];?></option>
    <?php } ?>
    </select>
</div>
<script>
    
    function getProductByAttributes(productId){
        
        const BASE_URL = "{{ url('/') }}"; 
        let urlGiven = `${BASE_URL}/products/getProductByAttributes`;
        $.ajax({
            url: urlGiven,
            type: "POST",
            data: { product_id: productId },
            success: function (response) {
                // response contains HTML
                $("#resultQuantity").html(response);
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message ?? "Error");
            }
        });
        
    }
</script>