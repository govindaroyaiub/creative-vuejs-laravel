<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Bill #{{ $bill->id }}</title>
    <style>
        @page {
            margin: 30px;
            size: A4;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 12px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        body {
            font-family: sans-serif;
            font-size: 14px;
            margin-bottom: 130px;
        }

        .logo {
            text-align: center;
        }

        .logo img {
            width: 270px;
            height: auto;
            margin-bottom: 10px;
        }

        h2 {
            margin-bottom: 10px;
            text-align: left;
        }

        p {
            margin: 2px 0;
        }

        .table-container {
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        thead {
            background: #0066cc;
            color: white;
            border-radius: 5px;
        }

        th {
            padding: 8px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }

        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        td {
            padding: 6px 8px;
            font-size: 11px;
        }

        td:nth-child(1),
        td:nth-child(2),
        td:nth-child(3),
        td:nth-child(4) {
            text-align: center;
        }

        /* Total Row */
        .total-row {
            background: #28a745 !important;
            color: white !important;
            font-weight: bold;
            border-radius: 5px;
        }

        .total-row td {
            font-size: 12px;
            padding: 10px 8px;
        }

        .total {
            text-align: left;
            margin-top: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .signature {
            margin-top: 60px;
            text-align: left;
        }

        .signature p {
            margin-bottom: 60px;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin-top: 5px;
        }

        .footer table {
            border: none;
            border-collapse: collapse;
        }

        .footer table th,
        .footer table td {
            border: none !important;
            padding: 2px 4px;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #0066cc;
            margin: 0;
            text-transform: uppercase;
        }

        .amount-words {
            margin: 15px 0;
            padding: 10px;
            background: #fff3cd;
            border-left: 3px solid #ffc107;
        }

        .amount-words p {
            margin: 0;
            font-size: 12px;
            font-weight: bold;
            color: #856404;
        }
    </style>
</head>

<body>
    <!-- Logo -->
    <div class="logo">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logos/planetnine.png'))) }}" alt="Planet Nine">
    </div>

    <!-- Bill Info -->
    <h2 class="invoice-title">Bill #{{ $bill->id }}</h2>
    <p><strong>Name:</strong> {{ $bill->name }}</p>
    <p><strong>Client:</strong> {{ $bill->client }}</p>
    <p><strong>Issue Date:</strong> {{ $issueDate }}</p>

    <!-- Sub-bill Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit Price (BDT)</th>
                    <th>Amount (BDT)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bill->subBills as $sub)
                <tr>
                    <td>{{ $sub->item }}</td>
                    <td>{{ number_format($sub->quantity) }}</td>
                    <td>{{ number_format($sub->unit_price, 2) }}</td>
                    <td>{{ number_format($sub->amount, 2) }}</td>
                </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="3"><strong>TOTAL AMOUNT</strong></td>
                    <td><strong>{{ number_format($bill->total_amount, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Total Amount -->
    <p class="amount-words">In Words: {{ $amountInWords }}</p>

    <!-- Signature Section -->
    <div class="signature">
        <p>Authorized By,</p>
        <div class="signature-line"></div>
    </div>

    <div class="footer">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr valign="top">
                <td width="25%" style="padding-right: 10px;">
                    <strong>Amsterdam Office</strong><br>
                    Vrijburglaan 39<br>
                    2051LB Overveen<br>
                    The Netherlands
                </td>
                <td width="25%" style="padding-right: 10px;">
                    <strong>Dhaka Office</strong><br>
                    House 943, Road 14<br>
                    Avenue 2, Mirpur DOHS<br>
                    Dhaka, Bangladesh
                </td>
                <td width="25%" style="padding-right: 10px;">
                    <strong>Contact (NL)</strong><br>
                    (+31) 653459211<br>
                    production@planetnine.com<br>
                    taco@planetnine.com
                </td>
                <td width="25%">
                    <strong>Contact (BD)</strong><br>
                    (+880) 1973330792<br>
                    limon@planetnine.com<br>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>