@php
    use App\Helpers\SettingsHelper;
    $contactInfo = SettingsHelper::getEmailContactInfo();
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <style>
        /* Menggunakan font sans-serif yang umum */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Tata letak header dengan tabel agar konsisten di PDF */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .header-table td {
            vertical-align: top;
            width: 50%;
        }

        .company-details p,
        .customer-details p {
            margin: 0;
            line-height: 1.5;
        }

        .company-logo {
            max-width: 240px;
            max-height: 120px;
            height: auto;
        }

        .customer-details {
            text-align: right;
        }

        .receipt-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 40px;
            letter-spacing: 2px;
        }

        .payment-details p {
            margin: 5px 0;
        }

        .payment-details-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .payment-details-table td {
            padding: 8px 0;
        }

        .payment-details-table .label {
            font-weight: bold;
            width: 150px;
            /* Lebar label agar titik dua sejajar */
        }

        .separator {
            border: 0;
            border-top: 1px solid #ccc;
            margin: 15px 0;
        }

        /* Kotak hijau untuk total */
        .total-amount-box {
            background-color: #8BC34A;
            /* Warna hijau dari gambar */
            color: #fff;
            padding: 20px;
            text-align: left;
            margin-top: 20px;
        }

        .total-amount-box .label {
            font-size: 14px;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .total-amount-box .amount {
            font-size: 20px;
            font-weight: bold;
        }

        .payment-for-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 40px;
            margin-bottom: 15px;
        }

        /* Tabel untuk detail invoice */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-table th,
        .invoice-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .invoice-table th {
            background-color: #343a40;
            /* Warna header tabel */
            color: white;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <table class="header-table">
            <tr>
                <img src="{{ asset('ecoforpest.png') }}" alt="Ecoforpest Logo" class="company-logo" />
            </tr>
            <tr>
                <td>
                    <div class="company-details">
                        <p><strong>{{ $contactInfo['company_name'] }}</strong></p>
                        <p>{{ $contactInfo['address'] }}</p>
                        <p>NPWP: {{ $contactInfo['npwp'] }}</p>
                    </div>
                </td>
                <td>
                    <div class="customer-details">
                        <p><strong>{{ $payment->invoice->customer->name }}</strong></p>
                        <p>{{ $payment->invoice->customer->address }}</p>
                        <p>{{ $payment->invoice->customer->zip_code }}</p>
                    </div>
                </td>
            </tr>
        </table>

        <h1 class="receipt-title">PAYMENT RECEIPT</h1>

        <table class="payment-details-table">
            <tr>
                <td class="label">Payment Date</td>
                <td>: {{ $payment->payment_date }}</td>
            </tr>
        </table>
        <hr class="separator">
        <table class="payment-details-table">
            <tr>
                <td class="label">Payment Mode</td>
                <td>: {{ $payment->payment_mode }}</td>
            </tr>
        </table>

        <div class="total-amount-box">
            <span class="label">Total Amount</span>
            <span class="amount">IDR{{ number_format($payment->amount, 2, ',', '.') }}</span>
        </div>

        <h2 class="payment-for-title">Payment For</h2>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Invoice Date</th>
                    <th>Invoice Amount</th>
                    <th>Payment Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $payment->invoice->invoice_number }}</td>
                    <td>{{ $payment->invoice->invoice_date }}</td>
                    <td>IDR{{ number_format($payment->invoice->total, 2, ',', '.') }}</td>
                    <td>IDR{{ number_format($payment->amount, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

    </div>
</body>

</html>
