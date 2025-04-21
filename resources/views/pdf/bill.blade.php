<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bill #{{ $bill->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h2>Bill #{{ $bill->id }}</h2>
    <p><strong>Name:</strong> {{ $bill->name }}</p>
    <p><strong>Client:</strong> {{ $bill->client }}</p>
    <p><strong>Total Amount:</strong> {{ number_format($bill->total_amount, 2) }}</p>
    <p><strong>Issue Date:</strong> {{ $issueDate }}</p>

    <h3>Sub-Bills</h3>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bill->subBills as $sub)
                <tr>
                    <td>{{ $sub->item }}</td>
                    <td>{{ $sub->quantity }}</td>
                    <td>{{ number_format($sub->unit_price, 2) }}</td>
                    <td>{{ number_format($sub->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>