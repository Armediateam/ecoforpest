<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $proposal->subject }}</title>
    <style>
        @page {
            size: A4;
            counter-increment: page;
        }

        body {
            font-family: sans-serif;
            color: #000;
            margin: 0;
            padding: 0;
            font-size: 10px;
        }

        .content {
            margin: 0;
        }

        @page {
            counter-increment: page;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin: 8px 0;
        }

        .table th {
            background: #002c22;
            padding: 6px 8px;
            text-align: left;
            color: #fff;
        }

        .table td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
        }

        .total-section {
            text-align: right;
        }

        .total-table {
            border-collapse: collapse;
            margin-left: auto;
            width: auto;
        }

        .total-table td {
            padding: 4px 0;
            border: none;
        }

        .total-table .label {
            text-align: right;
            font-weight: bold;
            padding-right: 10px;
        }

        .total-table .value {
            text-align: left;
        }

        .total-table .total-line-total td {
            font-weight: bold;
            font-size: 12px;
        }

        .terms-section {
            page-break-inside: avoid;
        }

        /* Compact spacing for lists and paragraphs */
        ul,
        ol {
            margin: 2px 0;
            padding-left: 20px;
        }

        li {
            margin: 2px 0;
            line-height: 1.2;
        }

        p {
            margin: 2px 0;
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

        /* PDF-compatible grid replacement using table layout */
        .pdf-grid {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }

        .pdf-grid td {
            padding: 4px;
            border: none;
            vertical-align: top;
        }

        .pdf-grid-2col td {
            width: 50%;
        }

        .pdf-grid-3col td {
            width: 33.33%;
        }

        .pdf-grid-4col td {
            width: 25%;
        }

        .pdf-grid-5col td {
            width: 20%;
        }

        .pdf-grid-6col td {
            width: 16.66%;
        }

        .pdf-grid p {
            margin: 4px 0;
            line-height: 1.3;
        }

        .pdf-grid strong {
            font-weight: bold;
        }

        /* Styling untuk tag <details> dan <summary> sebagai card minimalist modern elegan - versi compact */
        details {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin: 6px 0;
            background-color: #ffffff;
            overflow: hidden;
            transition: box-shadow 0.2s ease;
        }

        details:hover {
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        }

        summary {
            background-color: #f8f9fa;
            padding: 4px 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 10px;
            color: #333333;
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.2s ease;
            list-style: none;
            position: relative;
        }

        summary:hover {
            background-color: #e9ecef;
        }

        summary::after {
            content: '▶';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            transition: transform 0.2s ease;
            font-size: 10px;
            color: #666666;
        }

        details[open] summary::after {
            transform: translateY(-50%) rotate(90deg);
        }

        details[open] summary {
            border-bottom-color: #cccccc;
        }

        details>div {
            padding: 4px 6px;
            font-size: 10px;
            line-height: 1.3;
            color: #555555;
        }
    </style>
</head>

<body>
    <div class="content">
        {!! $content !!}
    </div>

    {{-- Include Additional Information and Terms --}}
    @php
        $hasClientNote = $order && !empty($order->client_note);
        $hasTerms = $order && !empty($order->terms_condition);
    @endphp

    @if ($hasClientNote || $hasTerms)
        <div class="terms-section">
            @if ($hasClientNote)
                <div class="info-section" style="margin-bottom: 8px;">
                    <h3 style="margin-bottom: 8px;">Additional Information</h3>
                    <div style="border:1px solid #eee; padding:8px; font-size:12px;">{!! $order->client_note !!}</div>
                </div>
            @endif

            @if ($hasTerms)
                <div class="info-section">
                    <h3 style="margin-bottom: 8px;">Additional Terms & Conditions</h3>
                    <div style="border:1px solid #eee; padding:8px; font-size:12px;">{!! $order->terms_condition !!}</div>
                </div>
            @endif
        </div>
    @endif
</body>

</html>
