<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $proposal->subject }} - Preview</title>
    <style>
        @page {
            margin: 100px 40px 80px 40px;
            counter-increment: page;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }

        .header {
            position: fixed;
            top: -70px;
            left: 0;
            right: 0;
            height: 50px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            padding: 10px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1a56db;
            margin: 0 0 5px 0;
        }

        .date {
            position: fixed;
            top: -85px;
            right: 0;
            font-size: 12px;
            color: #666;
        }

        .content {
            margin: 15px 0;
            padding-bottom: 40px;
        }

        .footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            min-height: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 30px;
            font-size: 12px;
            color: #666;
            background-color: #f8f9fa;
            border-top: 1px solid #ddd;
            box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.05);
        }

        .footer span {
            flex: 1;
            text-align: center;
        }

        .footer span:first-child {
            text-align: left;
        }

        .footer span:last-child {
            text-align: right;
        }

        .footer .page-number:after {
            content: 'Page ' counter(page);
        }

        @page {
            counter-increment: page;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            page-break-inside: avoid;
        }

        th,
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .items-table th {
            background-color: #f8f9fa;
            padding: 6px 8px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .items-table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }

        .total-section {
            margin-top: 15px;
            text-align: right;
        }

        .service-details {
            margin-top: 20px;
        }

        .terms-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        /* Compact spacing for lists and paragraphs */
        ul,
        ol {
            margin: 8px 0;
            padding-left: 20px;
        }

        li {
            margin: 2px 0;
            line-height: 1.2;
        }

        p {
            margin: 6px 0;
            line-height: 1.3;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            margin: 10px 0 5px 0;
            line-height: 1.2;
        }

        blockquote {
            margin: 10px 0;
            padding: 8px 15px;
            border-left: 4px solid #1a56db;
            background-color: #f8f9fa;
            font-style: italic;
            color: #555;
            border-radius: 0 4px 4px 0;
        }

        blockquote p {
            margin: 4px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ asset('ecoforpest.png') }}" alt="Company Logo" style="max-height: 50px; margin-bottom: 10px;">
    </div>

    <div class="footer">
        <span>{{ config('app.name', 'Ecoforpest') }}</span>
        <span class="page-number"></span>
        <span>{{ $proposal->subject }}</span>
    </div>

    <div class="date">
        {{ $date }}
    </div>

    <div class="content">
        {!! $content !!}
    </div>

    {{-- Include Items Table if there are items --}}
    @php
    $order = $proposal->proposalOrder?->first();
    $items = $order && method_exists($order, 'proposalItems') ? $order->proposalItems : collect();
    $hasItems = $items && $items->count() > 0;
    @endphp

    @if ($hasItems)
    <div class="items-section">
        <h3>Items & Services</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                <tr>
                    <td>{{ $item->name ?? '-' }}</td>
                    <td>{!! $item->description ?? '-' !!}</td>
                    <td>{{ $item->qty ?? '-' }}</td>
                    <td>Rp {{ isset($item->rate) ? number_format($item->rate, 0, ',', '.') : '-' }}</td>
                    <td>Rp {{ isset($item->amount) ? number_format($item->amount, 0, ',', '.') : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;"><strong>Subtotal:</strong></td>
                    <td>Rp {{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</td>
                </tr>
                @if (($order->discount_fixed ?? 0) > 0)
                <tr>
                    <td colspan="4" style="text-align:right;"><strong>Discount:</strong></td>
                    <td>Rp {{ number_format($order->discount_fixed, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if (($order->discount_percent ?? 0) > 0)
                <tr>
                    <td colspan="4" style="text-align:right;"><strong>Discount:</strong></td>
                    <td>{{ $order->discount_percent }}%</td>
                </tr>
                @endif
                @if ($order->adjustment)
                <tr>
                    <td colspan="4" style="text-align:right;"><strong>Adjustment:</strong></td>
                    <td>Rp {{ number_format($order->adjustment, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr>
                    <td colspan="4" style="text-align:right;"><strong>Total:</strong></td>
                    <td>Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    {{-- Include Service Details if available --}}
    @if ($order && isset($order->target_detail) && is_array($order->target_detail) && count($order->target_detail) > 0)
    <div class="service-details">
        <h3>Service Details</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Target</th>
                    <th>Treatment Area</th>
                    <th>Method</th>
                    <th>Unit/Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->target_detail as $target)
                <tr>
                    <td>{{ $target['target'] ?? '-' }}</td>
                    <td>{{ $target['treatment_area'] ?? '-' }}</td>
                    <td>{{ $target['method_use'] ?? '-' }}</td>
                    <td>{{ $target['unit_amount'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Include Additional Information and Terms --}}
    @php
    $hasClientNote = $order && !empty($order->client_note);
    $hasTerms = $order && !empty($order->terms_condition);
    @endphp

    @if ($hasClientNote || $hasTerms)
    <div class="terms-section">
        @if ($hasClientNote)
        <div class="info-section" style="margin-bottom: 15px;">
            <h3 style="margin-bottom: 8px;">Additional Information</h3>
            <div style="border:1px solid #eee; padding:8px; font-size:12px;">{!! $order->client_note !!}</div>
        </div>
        @endif

        @if ($hasTerms)
        <div class="info-section">
            <h3 style="margin-bottom: 8px;">Terms & Conditions</h3>
            <div style="border:1px solid #eee; padding:8px; font-size:12px;">{!! $order->terms_condition !!}</div>
        </div>
        @endif
    </div>
    @endif

</body>

</html>