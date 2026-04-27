<table>
    <tr>
        <td>Name</td>
        <td>Item Price</td>
        <td>Quantity</td>
        <td>Total Amount</td>
    </tr>
    <?php foreach($products["order_item"] as $row){ //print_r($row->toArray());?>
    <tr>
        <td>{{$row["product"]["name"]}}</td>
        <td>{{$row["price"]}}</td>
        <td>{{$row["quantity"]}}</td>
        <td>{{$row["total_price"]}}</td>
    </tr>
<?php } ?>
</table>