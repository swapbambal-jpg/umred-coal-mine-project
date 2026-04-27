<table border="1" cellspacing="0" cellpadding="6" width="100%" style="border-collapse: collapse; text-align: center;">
    <thead>
        <tr>
            <th style="text-align: center;">Sr No</th>
            <th style="text-align: left;">Product</th>
            <th style="text-align: right;">Price (₹)</th>
            <th style="text-align: center;">Qty</th>
            <th style="text-align: right;">Total (₹)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $i => $p)
            <tr>
                <td style="text-align: center;">{{ $i+1 }}</td>
                <td style="text-align: left;">{{ $p['name'] }}</td>
                <td style="text-align: right;">{{ number_format($p['price'], 2) }}</td>
                <td style="text-align: center;">{{ $p['qty'] }}</td>
                <td style="text-align: right;">{{ number_format($p['price'] * $p['qty'], 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="4" style="text-align: right;"><b>Grand Total</b></td>
            <td style="text-align: right;"><b>₹ {{ number_format($grandTotal, 2) }}</b></td>
        </tr>
    </tbody>
</table>
