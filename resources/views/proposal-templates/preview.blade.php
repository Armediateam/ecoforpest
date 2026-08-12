<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $template->name }} Preview</title>
    <style>
        @page {
            margin: 100px 40px 80px 40px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
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
            margin: 20px 0;
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
            margin: 20px 0;
            page-break-inside: avoid;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
        }

        /* PDF-compatible grid replacement using table layout */
        .pdf-grid {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .pdf-grid td {
            vertical-align: top;
            padding: 8px 12px;
            border: none;
        }

        .pdf-grid-2col td {
            width: 50%;
        }

        .pdf-grid p {
            margin: 4px 0;
            line-height: 1.3;
        }

        .pdf-grid strong {
            font-weight: bold;
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
    <div class="header">
        <img src="{{ $logoBase64 }}" alt="Company Logo" style="max-height: 50px; margin-bottom: 10px;">
    </div>
    <div class="footer">
        <span>Ecoforpest</span>
        <span class="page-number"></span>
        <span>{{ $template->name }}</span>
    </div>
    <div class="date">
        {{ $date }}
    </div>

    <div class="content">
        {!! $content !!}
    </div>



</body>

</html>