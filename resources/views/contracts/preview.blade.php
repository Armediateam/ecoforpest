<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $contract->subject }} - Preview</title>
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

        .contract-info {
            margin: 20px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .contract-info h1 {
            color: #1a56db;
            font-size: 24px;
            margin: 0 0 20px 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-section h2 {
            color: #4b5563;
            font-size: 16px;
            margin: 0 0 10px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .info-section p {
            margin: 5px 0;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ asset('ecoforpest.png') }}" alt="Company Logo" style="max-height: 50px; margin-bottom: 10px;">
    </div>

    @if ($contract->description)
        <div class="info-section">
            <h2>Description</h2>
            <p>{{ $contract->description }}</p>
        </div>
    @endif
    </div>
    </div>

</body>

</html>
