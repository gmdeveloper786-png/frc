<!DOCTYPE html>
<html lang="en" class="receipt-pdf-html">
<head>
<meta charset="UTF-8">
<title>Receipt {{ $receipt['receipt_number'] }}</title>
<style>
{!! file_get_contents(public_path('css/receipt.css')) !!}
</style>
</head>
<body class="receipt-pdf-body">
<table class="receipt-pdf-layout" cellpadding="0" cellspacing="0">
<tr>
<td class="receipt-pdf-layout-cell">
<div class="receipt-card">
    @include('payments.partials.receipt-content')
</div>
</td>
</tr>
</table>
</body>
</html>
