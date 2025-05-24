<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Bill #{{ $bill->id }}</title>
    <style>
        @page {
            margin: 40px;
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
            /* reserve space for the footer */
        }

        .logo {
            text-align: left;
        }

        .logo img {
            width: 200px;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #ccc;
            text-align: center;
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
    </style>
</head>

<body>
    <!-- Logo -->
    <div class="logo">
        <img src="https://staging.planetnine.com/wp-content/uploads/2025/04/logo.png" alt="Planet Nine">
    </div>

    <!-- Bill Info -->
    <h2>Bill #{{ $bill->id }}</h2>
    <p><strong>Name:</strong> {{ $bill->name }}</p>
    <p><strong>Client:</strong> {{ $bill->client }}</p>
    <p><strong>Issue Date:</strong> {{ $issueDate }}</p>

    <!-- Sub-bill Table -->
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

            <!-- Total Row -->
            <tr>
                <td colspan="3" style="text-align: center; font-weight: bold;">Total Amount</td>
                <td style="text-align: center; font-weight: bold;">{{ number_format($bill->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Total Amount -->
    <p class="total">In Words: {{ $amountInWords }}</p>

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