@php
    use App\Helpers\SettingsHelper;
    $contactInfo = SettingsHelper::getEmailContactInfo();
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        {{-- css reset --}} h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        p,
        div,
        span,
        table,
        th,
        td,
        ul,
        ol,
        li {
            margin: 0;
            padding: 0;
            font-weight: normal;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', Arial, sans-serif;
            color: #333;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.3;
        }

        .invoice-box {
            max-width: 190mm;
            margin: 0 auto;
            background: white;
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 2px solid #007c42;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .logo {
            max-width: 240px;
            max-height: 120px;
            height: auto;
        }

        .company-info {
            margin-top: 8px;
            font-size: 9px;
            color: #666;
            line-height: 1.2;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #007c42;
            margin: 0 0 8px 0;
            letter-spacing: 1px;
        }

        .invoice-number {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
            border-color: #28a745;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffc107;
        }

        .status-overdue {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #dc3545;
        }

        .status-draft {
            background-color: #e2e3e5;
            color: #383d41;
            border-color: #6c757d;
        }

        .invoice-dates {
            margin-top: 10px;
            font-size: 10px;
            line-height: 1.2;
        }

        .invoice-dates strong {
            color: #333;
        }

        /* Billing Information */
        .billing-info {
            display: table;
            width: 100%;
            margin: 15px 0;
        }

        .billing-from,
        .billing-to {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 0 8px;
        }

        .billing-from {
            padding-left: 0;
        }

        .billing-to {
            padding-right: 0;
            text-align: right;
        }

        .billing-label {
            font-size: 10px;
            font-weight: bold;
            color: #007c42;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 6px;
            display: block;
        }

        .company-name {
            font-weight: bold;
            font-size: 11px;
            color: #333;
            margin-bottom: 3px;
        }

        .address-line {
            color: #666;
            margin-bottom: 1px;
            font-size: 10px;
            line-height: 1.2;
        }

        .customer-info {
            background-color: #f8f9fa;
            padding: 8px;
            border-left: 3px solid #007c42;
            margin-top: 6px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background-color: white;
            border: 1px solid #dee2e6;
        }

        .items-table thead {
            background-color: #999999 !important;
            color: white !important;
        }

        .items-table thead th {
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #999999;
            background-color: #999999 !important;
            color: white !important;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #e9ecef;
        }

        .items-table tbody tr:last-child {
            border-bottom: 2px solid #007c42;
        }

        .items-table tbody td {
            padding: 8px 6px;
            vertical-align: top;
            font-size: 10px;
            border-right: 1px solid #f0f0f0;
        }

        .items-table tbody td:last-child {
            border-right: none;
        }

        .item-number {
            width: 30px;
            text-align: center;
            font-weight: bold;
            color: #007c42;
        }

        .item-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 2px;
            font-size: 10px;
        }

        .item-description {
            font-size: 8px;
            color: #666;
            line-height: 1.2;
            margin-top: 2px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .currency {
            font-weight: 600;
            color: #333;
        }

        /* Totals Section */
        .totals-section {
            margin-top: 15px;
            display: table;
            width: 100%;
        }

        .totals-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .totals-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }

        .totals-table {
            width: 100%;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .totals-table tbody tr {
            border-bottom: 1px solid #dee2e6;
        }

        .totals-table tbody tr:last-child {
            border-bottom: none;
        }

        .totals-table td {
            padding: 6px 10px;
            font-size: 10px;
        }

        .total-label {
            font-weight: 600;
            color: #495057;
        }

        .total-value {
            text-align: right;
            font-weight: 600;
            color: #333;
        }

        .amount-due-row {
            background-color: #999999 !important;
            color: white !important;
        }

        .amount-due-row td {
            font-weight: bold;
            font-size: 11px;
            padding: 8px 10px;
            background-color: #999999 !important;
            color: white !important;
        }

        /* Footer Sections */
        .footer-section {
            margin-top: 5px;
            padding: 12px 0;
            border-top: 1px solid #e9ecef;
            page-break-inside: avoid;
        }

        .footer-section:first-of-type {
            margin-top: 10px;
        }

        .footer-title {
            font-size: 11px;
            font-weight: bold;
            color: #007c42;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 6px;
        }

        .payment-info {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            width: 100%;
        }

        .payment-method {
            flex: 1;
            min-width: 45%;
            padding: 6px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            vertical-align: top;
            box-sizing: border-box;
        }

        .bank-name {
            font-weight: bold;
            color: #007c42;
            margin-bottom: 3px;
            font-size: 9px;
        }

        .account-number {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: bold;
            color: #333;
            background-color: white;
            padding: 3px 5px;
            border: 1px solid #ccc;
            display: inline-block;
            margin-top: 2px;
        }

        .footer-content {
            color: #666;
            line-height: 1.3;
            font-size: 9px;
        }

        .footer-content strong {
            color: #333;
        }

        .divider {
            border: none;
            border-top: 1px solid #dee2e6;
            margin: 12px 0;
        }

        ul {
            padding-left: 20px;
            margin-top: 2px;
        }

        ol {
            padding-left: 20px;
            margin-top: 2px;
        }

        ul li,
        ol li {
            margin-bottom: 4px;
        }

        /* Print and PDF Optimizations */
        @media print {
            body {
                font-size: 10px;
                margin: 0;
                padding: 0;
                background: white;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .invoice-box {
                max-width: none;
                width: 100%;
                margin: 0;
                box-shadow: none;
                border: none;
                page-break-after: avoid;
            }

            .header {
                margin-bottom: 12px;
                page-break-after: avoid;
            }

            .billing-info {
                margin: 12px 0;
                page-break-inside: avoid;
            }

            .items-table {
                margin: 12px 0;
                page-break-inside: auto;
            }

            .items-table thead {
                display: table-header-group;
                page-break-after: avoid;
                background-color: #999999 !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .items-table thead th {
                background-color: #999999 !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .items-table tbody tr {
                page-break-inside: avoid;
            }

            .amount-due-row,
            .amount-due-row td {
                background-color: #999999 !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .totals-section {
                margin-top: 12px;
                page-break-inside: avoid;
            }

            .footer-section {
                margin-top: 15px;
                padding-top: 10px;
                page-break-inside: avoid;
            }

            .payment-info {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                width: 100%;
            }

            .payment-method {
                flex: 1;
                min-width: 45%;
                padding: 5px;
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                vertical-align: top;
                box-sizing: border-box;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Screen-only styles */
        @media screen {
            .invoice-box {
                box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
            }
        }

        /* Additional utility classes */
        .page-break {
            page-break-before: always;
        }

        .no-break {
            page-break-inside: avoid;
        }

        .text-muted {
            color: #6c757d;
        }

        .text-success {
            color: #28a745;
        }

        .text-warning {
            color: #ffc107;
        }

        .text-danger {
            color: #dc3545;
        }

        .small {
            font-size: 9px;
        }

        /* PDF-safe borders and backgrounds */
        .pdf-border {
            border: 1px solid #333;
        }

        .pdf-bg-light {
            background-color: #f5f5f5;
        }

        .pdf-bg-primary {
            background-color: #007c42;
            color: white;
        }

        /* Alternative styling for PDF compatibility */
        .pdf-header-alt {
            border: 3px solid #999999;
            font-weight: bold;
            text-transform: uppercase;
        }

        .pdf-total-alt {
            border: 2px solid #999999;
            font-weight: bold;
            border-top: 3px solid #999999;
            border-bottom: 3px solid #999999;
        }

        /* Force background colors for better PDF support */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    </style>
</head>

<body>
    @php
        $logoPath = public_path('ecoforpest.png');
        // Pastikan file ada sebelum mencoba membacanya
        if (file_exists($logoPath)) {
            $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
        } else {
            $logoBase64 = '';
        }
    @endphp
    <div class="invoice-box">
        <!-- Header Section -->
        <div class="header no-break">
            <div class="header-left">
                <img src="{{ $logoBase64 }}" alt="Ecoforpest Logo" class="logo" />
            </div>
            <div class="header-right">
                <h1 class="invoice-title">{{ $invoice->is_quotation ? 'QUOTATION' : 'INVOICE' }}</h1>
                <div class="invoice-number"># {{ $invoice->invoice_number }}</div>
                <div class="status-badge status-{{ strtolower($invoice->status) }}">
                    {{ strtoupper($invoice->status) }}
                </div>
                <div class="invoice-dates">
                    <div><strong>{{ $invoice->is_quotation ? 'Quotation' : 'Invoice' }} Date:</strong>
                        {{ $invoice->invoice_date }}</div>
                    <div><strong>Due Date:</strong> {{ $invoice->invoice_due_date }}</div>
                </div>
            </div>
        </div>

        <!-- Billing Information -->
        <div class="billing-info no-break">
            <div class="billing-from">
                <span class="billing-label">From</span>
                <div class="company-name">{{ $contactInfo['company_name'] }}</div>
                <div class="address-line">{{ $contactInfo['address'] }}</div>
                <div class="address-line"><strong>NPWP:</strong> {{ $contactInfo['npwp'] }}</div>
            </div>
            <div class="billing-to">
                <span class="billing-label">Bill To</span>
                @if ($invoice->customer)
                    <div class="customer-info">
                        <div class="company-name">{{ $invoice->customer->company ?? '-' }}</div>
                        <div class="company-name">{{ $invoice->customer->name ?? '-' }}</div>
                        <div class="address-line">{!! nl2br(e($invoice->billing_address ?? 'Address not specified')) !!}</div>
                        <div class="address-line"><strong>Customer ID:</strong> {{ $invoice->customer->id ?? '-' }}
                        </div>
                    </div>
                @elseif ($invoice->lead)
                    <div class="customer-info">
                        <div class="company-name">{{ $invoice->lead->company ?? '-' }}</div>
                        <div class="company-name">{{ $invoice->lead->name ?? '-' }}</div>
                        <div class="address-line">{!! nl2br(e($invoice->billing_address ?? 'Address not specified')) !!}</div>
                        <div class="address-line"><strong>Lead ID:</strong> {{ $invoice->lead->id ?? '-' }}</div>
                    </div>
                @else
                    <div class="customer-info">
                        <div class="address-line">No customer or lead information available</div>
                    </div>
                @endif
            </div>
        </div>
        @if ($invoice->invoiceItem->count() > 0)
            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr style="background-color: #999999 !important; color: white !important;">
                        <th class="text-center" style="background-color: #999999 !important; color: white !important;">
                            No
                        </th>
                        <th style="background-color: #999999 !important; color: white !important;">Item Description</th>
                        <th class="text-center" style="background-color: #999999 !important; color: white !important;">
                            Qty
                        </th>
                        <th class="text-right" style="background-color: #999999 !important; color: white !important;">
                            Rate
                        </th>
                        <th class="text-center" style="background-color: #999999 !important; color: white !important;">
                            Tax
                        </th>
                        <th class="text-right" style="background-color: #999999 !important; color: white !important;">
                            Amount
                            Inc. Tax
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->invoiceItem as $index => $item)
                        <tr>
                            <td class="item-number text-center">{{ $index + 1 }}</td>
                            <td>
                                <div class="item-name">{{ $item->name }}</div>
                                @if ($item->description)
                                    <div class="item-description">
                                        {!! nl2br(e($item->description)) !!}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format($item->qty, 0, ',', '.') }}</td>
                            <td class="text-right currency">IDR {{ number_format($item->rate, 2, ',', '.') }}</td>
                            <td class="text-center small">
                                @if ($item->taxes && $item->taxes->count() > 0)
                                    @foreach ($item->taxes as $tax)
                                        <div>{{ $tax->name }} ({{ $tax->value }}%)</div>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-right currency">IDR {{ number_format($item->amount, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        @if ($invoice->invoiceService && $invoice->invoiceService->count() > 0)
            <table class="items-table">
                <thead>
                    <tr style="background-color: #999999 !important; color: white !important;">
                        <th class="text-center" style="background-color: #999999 !important; color: white !important;">
                            No
                        </th>
                        <th style="background-color: #999999 !important; color: white !important;">Service Description
                        </th>
                        <th class="text-center" style="background-color: #999999 !important; color: white !important;">
                            Qty
                        </th>
                        <th class="text-right" style="background-color: #999999 !important; color: white !important;">
                            Rate
                        </th>
                        <th class="text-center" style="background-color: #999999 !important; color: white !important;">
                            Tax
                        </th>
                        <th class="text-right" style="background-color: #999999 !important; color: white !important;">
                            Amount
                            Inc. Tax
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->invoiceService as $index => $service)
                        <tr>
                            <td class="item-number text-center">{{ $index + 1 }}</td>
                            <td>
                                <div class="item-name">{{ $service->name }}</div>
                                @if ($service->description)
                                    <div class="item-description">
                                        {!! nl2br(e($service->description)) !!}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format($service->qty, 0, ',', '.') }}</td>
                            <td class="text-right currency">IDR {{ number_format($service->rate, 2, ',', '.') }}</td>
                            <td class="text-center small">
                                @if ($service->taxes && $service->taxes->count() > 0)
                                    @foreach ($service->taxes as $tax)
                                        <div>{{ $tax->name }} ({{ $tax->value }}%)</div>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-right currency">IDR {{ number_format($service->amount, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @php
            $paymentPaid = \App\Models\Payment::where('invoice_id', $invoice->id);
            $totalPaid = $paymentPaid->sum('amount');
        @endphp
        <!-- Totals Section -->
        <div class="totals-section no-break">
            <div class="totals-left">
                <!-- Space for additional information if needed -->
            </div>
            <div class="totals-right">
                <table class="totals-table">
                    <tbody>
                        <tr>
                            <td class="total-label">Subtotal</td>
                            <td class="total-value currency">IDR {{ number_format($invoice->subtotal, 2, ',', '.') }}
                            </td>
                        </tr>
                        @if ($invoice->discount_fixed)
                            <tr>
                                <td class="total-label">Discount Fixed</td>
                                <td class="total-value currency">
                                    - IDR {{ number_format($invoice->discount_fixed, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                        @if ($invoice->discount_percent)
                            <tr>
                                <td class="total-label">Discount (%)</td>
                                <td class="total-value currency">
                                    - IDR
                                    {{ number_format(($invoice->subtotal * $invoice->discount_percent) / 100, 2, ',', '.') }}
                                    ({{ $invoice->discount_percent }}%)
                                </td>
                            </tr>
                        @endif
                        @if ($invoice->adjusment)
                            <tr>
                                <td class="total-label">Adjustment</td>
                                <td class="total-value currency">IDR
                                    {{ number_format($invoice->adjutment, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td class="total-label">Total</td>
                            <td class="total-value currency">IDR {{ number_format($invoice->total, 2, ',', '.') }}</td>
                        </tr>
                        @if (strtolower($invoice->status) === 'down payment')
                            <tr>
                                <td class="total-label">Total Paid</td>
                                <td class="total-value currency">IDR {{ number_format($totalPaid, 2, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr class="amount-due-row"
                            @if (strtolower($invoice->status) === 'down payment') style="background-color: #dc3545 !important; color: white !important;"
                            @else
                            style="background-color: #999999 !important; color: white !important;" @endif>
                            <td
                                @if (strtolower($invoice->status) === 'down payment') style="background-color: #dc3545 !important; color: white !important;"
                                @else
                                style="background-color: #999999 !important; color: white !important;" @endif>
                                Amount Due</td>
                            <td class="text-right"
                                @if (strtolower($invoice->status) === 'down payment') style="background-color: #dc3545 !important; color: white !important;"
                                @else
                                style="background-color: #999999 !important; color: white !important;" @endif>
                                IDR
                                @if (strtolower($invoice->status) === 'down payment')
                                    {{ number_format($invoice->total - $totalPaid, 2, ',', '.') }}
                                @else
                                    {{ number_format($invoice->total, 2, ',', '.') }}
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="divider"></div>
        @if (!empty($invoice->target_detail))
            @php
                $serviceDetails = $invoice->target_detail;
            @endphp
            @if (is_array($serviceDetails) && !empty($serviceDetails) && isset($serviceDetails[0]['target']))
                <div class="footer-section no-break" style="border-top: none; margin-top: 5px; padding-top: 0;">
                    <h4 class="footer-title">Service Detail</h4>
                    <div class="footer-content">
                        {{-- @foreach ($serviceDetails as $detail) --}}
                        <div class="no-break" style="margin-top: 5px;">
                            <table class="items-table" style="margin-top: 5px;">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Target</th>
                                        <th>Treatment Area</th>
                                        <th>Method</th>
                                        <th>Unit / Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($serviceDetails as $index => $detail)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $detail['target'] ?? '-' }}</td>
                                            <td>{{ $detail['treatment_area'] ?? '-' }}</td>
                                            <td>{{ $detail['method_use'] ?? '-' }}</td>
                                            <td>{{ $detail['unit_amount'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{-- @endforeach --}}
                    </div>
                </div>
            @endif
        @endif
        <!-- Payment Information -->
        @php
            // Get banks data from settings
            $banksSetting = \App\Models\Setting::where('key', 'banks')->first();
            $banksData = [];

            if ($banksSetting && $banksSetting->value) {
                $banksData = is_array($banksSetting->value)
                    ? $banksSetting->value
                    : json_decode($banksSetting->value, true);
            }

            // Get allowed payment methods for this invoice
            $allowedMethods = $invoice->allowed_payment_method ?? [];

            // Filter banks data to only show allowed payment methods
            $allowedBanks = [];
            if (is_array($allowedMethods) && is_array($banksData)) {
                foreach ($allowedMethods as $method) {
                    if (isset($banksData[$method])) {
                        $allowedBanks[$method] = $banksData[$method];
                    }
                }
            }
        @endphp

        @if (!empty($allowedBanks))
            <div class="footer-section no-break">
                <h4 class="footer-title">Payment Information</h4>
                <div class="payment-info">
                    @foreach ($allowedBanks as $bankName => $accountNumber)
                        @if ($bankName !== 'Tunai')
                            <div class="payment-method">
                                <div class="bank-name">{{ $bankName }}</div>
                                <div class="account-number">{{ $accountNumber }}</div>
                            </div>
                        @else
                            <div class="payment-method">
                                <div class="bank-name">{{ $bankName }}</div>
                                <div class="account-number"
                                    style="background-color: #fff3cd; color: #856404; border-color: #ffc107;">
                                    {{ $accountNumber }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Additional Notes -->
        @if ($invoice->client_note)
            <div class="footer-section no-break">
                <h4 class="footer-title">Notes</h4>
                <div class="footer-content">
                    {!! $invoice->client_note !!}
                </div>
            </div>
        @endif

        <!-- Terms & Conditions -->
        @if ($invoice->terms_condition)
            <div class="footer-section no-break">
                <h4 class="footer-title">Terms & Conditions</h4>
                <div class="footer-content">
                    {!! $invoice->terms_condition !!}
                </div>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer-section no-break small text-center text-muted" style="border-top: none; margin-top: 25px;">
            <p style="margin: 0; font-size: 8px;">This {{ $invoice->is_quotation ? 'quotation' : 'invoice' }} was
                generated electronically and is valid without
                signature.</p>
            <p style="margin: 2px 0 0 0; font-size: 8px;">Thank you for your business!</p>
        </div>
    </div>

</body>

</html>
