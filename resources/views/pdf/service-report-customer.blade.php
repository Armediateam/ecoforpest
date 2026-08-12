<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Service Report - {{ $reportNumber }}</title>
    @php
        $contactInfo = \App\Helpers\SettingsHelper::getContactInformation();
    @endphp
    <style>
        @page {
            margin: 100px 25px 60px 25px;
        }

        body {
            font-family: sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.3;
        }

        .header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 75px;
            border-bottom: 2px solid #204417;
            padding-bottom: 0px;
        }

        .footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            height: 30px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            font-size: 8pt;
            color: #6b7280;
            text-align: center;
        }

        .logo {
            float: left;
            height: 50px;
            margin-top: 5px;
        }

        .header-right {
            float: right;
            text-align: right;
        }

        .report-title {
            font-size: 16pt;
            font-weight: bold;
            color: #204417;
            text-transform: uppercase;
            letter-spacing: -0.5px;
            margin: 0;
        }

        .report-meta {
            font-size: 9pt;
            color: #6b7280;
            margin-top: 2px;
        }

        /* Layout */
        .container {
            width: 100%;
            margin-top: 10px;
        }

        .row {
            width: 100%;
            clear: both;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .col-6 {
            width: 48%;
            float: left;
        }

        .col-6:first-child {
            margin-right: 4%;
        }

        .col-6:last-child {
            float: right;
        }

        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }

        /* Sections */
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: #fff;
            background-color: #204417;
            padding: 6px 10px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        th,
        td {
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9pt;
            vertical-align: top;
        }

        th {
            background-color: #f3f4f6;
            color: #374151;
            width: 35%;
            font-weight: bold;
        }

        td {
            color: #111827;
        }

        tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        tr:last-child th,
        tr:last-child td {
            border-bottom: 1px solid #e5e7eb;
        }

        /* Scope */
        .scope-item {
            margin-bottom: 8px;
            padding: 10px 12px;
            background-color: #fff;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #204417;
            border-radius: 4px;
            page-break-inside: avoid;
        }

        .scope-title {
            font-weight: bold;
            font-size: 9.5pt;
            color: #111827;
        }

        .scope-desc {
            font-size: 9pt;
            color: #4b5563;
            margin-top: 2px;
        }

        /* Photos */
        .photo-grid {
            width: 100%;
            margin-top: 10px;
            clear: both;
        }

        .photo-cell {
            width: 30%;
            display: inline-block;
            vertical-align: top;
            margin-bottom: 15px;
            margin-right: 2%;
            text-align: center;
            page-break-inside: avoid;
        }

        .photo-img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }

        .photo-caption {
            font-size: 8pt;
            color: #6b7280;
            margin-top: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Signatures */
        .signature-box {
            margin-top: 30px;
            background-color: #fff;
            padding: 20px;
            border-radius: 6px;
            page-break-inside: avoid;
            border: 2px dashed #d1d5db;
        }

        .sig-row {
            margin-top: 30px;
        }

        .sig-col {
            width: 45%;
            float: left;
            text-align: center;
        }

        .sig-col:last-child {
            float: right;
        }

        .sig-line {
            border-top: 1px solid #d1d5db;
            margin-top: 10px;
            padding-top: 5px;
            font-weight: bold;
            font-size: 9pt;
            color: #374151;
        }

        .sig-img {
            height: 50px;
            object-fit: contain;
        }

        /* Utilities */
        .text-right {
            text-align: right;
        }

        .text-bold {
            font-weight: bold;
        }

        .text-success {
            color: #059669;
        }

        .no-data {
            font-style: italic;
            color: #9ca3af;
            font-size: 9pt;
            padding: 10px;
            text-align: center;
            background: #f9fafb;
            border-radius: 4px;
            border: 1px dashed #e5e7eb;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 8pt;
            font-weight: bold;
            background: #e5e7eb;
            color: #374151;
        }

        .service-result-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
        }

        .service-result-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .survey-img {
            max-width: 100%;
            height: auto;
            max-height: 250px;
            display: block;
            margin: 5px 0;
            page-break-inside: avoid;
        }

        .survey-container {
            width: 100%;
            margin-bottom: 20px;
            page-break-inside: auto;
        }

        .survey-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #e5e7eb;
            page-break-inside: avoid;
        }

        .survey-label {
            display: table-cell;
            width: 35%;
            font-weight: bold;
            color: #374151;
            background-color: #f3f4f6;
            padding: 10px 8px;
            vertical-align: top;
        }

        .survey-content {
            display: table-cell;
            width: 65%;
            padding: 10px 8px;
            vertical-align: top;
        }

        .survey-img-container {
            width: 100%;
            margin-top: 5px;
            font-size: 0;
        }

        .survey-img-item {
            display: inline-block;
            width: 30%;
            margin: 0 1% 10px 0;
            vertical-align: top;
        }

        .survey-img-item img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <img src="{{ asset('ecoforpest.png') }}" class="logo" alt="Logo" />
        <div class="header-right">
            <div class="report-title">Service Report</div>
            <div class="report-meta">#{{ $reportNumber }}</div>
            <div class="report-meta">{{ $date }} • {{ $time }}</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        {{ $contactInfo['company_name'] }}
        @if (!empty($contactInfo['website']))
            • {{ $contactInfo['website'] }}
        @endif
        @if (!empty($contactInfo['phone']))
            • {{ $contactInfo['phone'] }}
        @endif
    </div>

    <!-- Content -->
    <div class="container">

        <!-- Info Grid -->
        <div class="row clearfix">
            <div class="col-6">
                <div class="section-title">Customer</div>
                <table>
                    <tr>
                        <th>Name</th>
                        <td><strong>{{ ($workOrder->customer ?? $workOrder->lead)->name ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td>{{ ($workOrder->customer ?? $workOrder->lead)->address ?? ($workOrder->alamat ?? '-') }}
                        </td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td>{{ ($workOrder->customer ?? $workOrder->lead)->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ ($workOrder->customer ?? $workOrder->lead)->email ?? '-' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-6">
                <div class="section-title">Service Details</div>
                <table>
                    <tr>
                        <th>WO Number</th>
                        <td><strong>WO-{{ $workOrder->id }}</strong></td>
                    </tr>
                    <tr>
                        <th>Service Type</th>
                        <td>{{ $workOrder->service->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Date</th>
                        <td>{{ $workOrder->work_date ? \Carbon\Carbon::parse($workOrder->work_date)->format('d M Y') : '-' }}
                            {{ $workOrder->work_time ? '• ' . $workOrder->work_time : '' }}</td>
                    </tr>
                    <tr>
                        <th>Technician</th>
                        <td>{{ $workOrder->assigned->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="text-success text-bold">{{ $workOrder->status ?? '-' }}</span></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Scope of Work -->
        <div class="row">
            <div class="section-title">Scope of Work</div>
            @if (!empty($workOrder->tindakan) && count($workOrder->tindakan) > 0)
                @foreach ($workOrder->tindakan as $i => $t)
                    <div class="scope-item">
                        <div class="scope-title">{{ $i + 1 }}. {{ $t['name'] ?? 'Unnamed Task' }}</div>
                        @if (!empty($t['description']))
                            <div class="scope-desc">{{ $t['description'] }}</div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="no-data">No specific work scope recorded.</div>
            @endif
        </div>

        <!-- Service Results / Survey -->
        @if ($workOrder->survey_with_answers)
            <div class="row">
                <div class="section-title">Service Results</div>
                @foreach ($workOrder->survey_with_answers as $evaluationSurvey)
                    <div class="survey-container">
                        <div
                            style="color: #374151; margin-bottom: 5px; font-size: 10pt; border-bottom: 2px solid #204417; padding-bottom: 5px;">
                            <strong>{{ $evaluationSurvey['form'] ?? 'Evaluation' }}</strong>
                        </div>

                        @foreach ($evaluationSurvey['fields'] as $field)
                            <div class="survey-row">
                                <div class="survey-label">{{ $field['label'] }}</div>
                                <div class="survey-content">
                                    @if (is_array($field['answer']))
                                        @if ($field['type'] === 'file')
                                            <div class="survey-img-container">
                                                @foreach ($field['answer'] as $file)
                                                    @php
                                                        $path = parse_url($file['url'], PHP_URL_PATH);
                                                        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                                        $isImage = in_array($extension, [
                                                            'jpg',
                                                            'jpeg',
                                                            'png',
                                                            'gif',
                                                            'webp',
                                                        ]);
                                                    @endphp

                                                    @if ($isImage)
                                                        <img src="{{ $file['url'] }}" class="survey-img-item">
                                                    @else
                                                        <div style="margin-bottom: 5px;">
                                                            <a href="{{ $file['url'] }}" target="_blank"
                                                                style="color: #2563eb;">View File</a>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            {{ implode(', ', $field['answer']) }}
                                        @endif
                                    @else
                                        @if ($field['type'] === 'signature')
                                            <img src="{{ $field['answer'] ?? '' }}" style="height: 50px;">
                                        @else
                                            {{ $field['answer'] ?? '-' }}
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Photos -->
        @php
            $allPhotos = [];
            if (!empty($progressPhotos) && is_array($progressPhotos)) {
                foreach ($progressPhotos as $url) {
                    if ($url) {
                        $allPhotos[] = ['url' => $url, 'caption' => 'Progress'];
                    }
                }
            }
            if (!empty($workOrder->surveyWithAnswers)) {
                foreach ($workOrder->surveyWithAnswers as $survey) {
                    foreach ($survey['fields'] as $field) {
                        if ($field['type'] === 'file' && !empty($field['answer'])) {
                            foreach ($field['answer'] as $file) {
                                if (!empty($file['url'])) {
                                    $allPhotos[] = ['url' => $file['url'], 'caption' => $field['label']];
                                }
                            }
                        }
                    }
                }
            }
        @endphp

        @if (count($allPhotos) > 0)
            <div class="row">
                <div class="section-title">Documentation</div>
                <div class="photo-grid">
                    @foreach ($allPhotos as $index => $photo)
                        <div class="photo-cell">
                            <img src="{{ $photo['url'] }}" class="photo-img">
                            <div class="photo-caption">{{ $photo['caption'] }}</div>
                        </div>

                        @if (($index + 1) % 3 == 0)
                            <div style="clear: both; height: 0; line-height: 0;"></div>
                        @endif
                    @endforeach
                    <div style="clear: both;"></div>
                </div>
            </div>
        @endif

        <!-- Signatures -->
        <div class="signature-box">
            <div style="font-size: 9pt; color: #6b7280; margin-bottom: 15px; text-align: center; font-style: italic;">
                This document confirms that the requested pest control service has been completed according to the
                agreed scope of work.
            </div>
            <div class="clearfix sig-row">
                <div class="sig-col">
                    <div
                        style="height: 50px; margin-bottom: 5px; display: flex; align-items: center; justify-content: center;">
                        @if (!empty($report->Client_signature_url))
                            <img src="{{ $report->Client_signature_url }}" class="sig-img">
                        @else
                            <span style="color: #e5e7eb; font-size: 8pt;">(No Signature)</span>
                        @endif
                    </div>
                    <div class="sig-line">Customer: {{ $report->client_signature_name ?? 'Signed' }}</div>
                </div>
                <div class="sig-col">
                    <div
                        style="height: 50px; margin-bottom: 5px; display: flex; align-items: center; justify-content: center;">
                        @if (!empty($report->technician_signature_url))
                            <img src="{{ $report->technician_signature_url }}" class="sig-img">
                        @else
                            <span style="color: #e5e7eb; font-size: 8pt;">(No Signature)</span>
                        @endif
                    </div>
                    <div class="sig-line">Technician: {{ $report->technician_name ?? 'Signed' }}</div>
                </div>
            </div>
        </div>

    </div>
</body>

</html>
