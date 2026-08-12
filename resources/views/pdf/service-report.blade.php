<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Service Report - Internal</title>

    @php
        $contactInfo = \App\Helpers\SettingsHelper::getContactInformation();
    @endphp
    <style>
        @page {
            margin: 2cm;

            @if ($report->client_approve)
                <span class="status-badge status-signed">Signed</span>
            @else
                <span class="status-badge status-pending">Pending Signature</span>
            @endif
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #2c3e50;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px 0;
            border-bottom: 3px solid #e74c3c;
            position: relative;
        }

        .header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #e74c3c 0%, #c0392b 100%);
        }

        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header .subtitle {
            margin-top: 5px;
            color: #7f8c8d;
            font-size: 12px;
            font-style: italic;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            background: linear-gradient(90deg, #34495e 0%, #2c3e50 100%);
            color: white;
            padding: 12px 15px;
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .info-table,
        .survey-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            overflow: hidden;
        }

        .info-table th,
        .info-table td {
            border: 1px solid #bdc3c7;
            padding: 12px 15px;
            font-size: 11px;
        }

        .info-table th {
            background: linear-gradient(90deg, #ecf0f1 0%, #d5dbdb 100%);
            font-weight: bold;
            color: #2c3e50;
            width: 35%;
            text-align: left;
        }

        .info-table td {
            background-color: #ffffff;
        }

        .survey-table th,
        .survey-table td {
            border: 1px solid #bdc3c7;
            padding: 10px 12px;
            font-size: 10px;
        }

        .survey-table th {
            background: linear-gradient(90deg, #3498db 0%, #2980b9 100%);
            color: white;
            font-weight: bold;
            text-align: center;
        }

        .survey-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .survey-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .survey-table tbody tr:hover {
            background-color: #e8f4fd;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-signed {
            background-color: #27ae60;
            color: white;
        }

        .status-pending {
            background-color: #f39c12;
            color: white;
        }

        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .signature {
            display: table-cell;
            width: 45%;
            text-align: center;
            padding: 20px;
            border: 2px dashed #bdc3c7;
            border-radius: 8px;
            margin: 10px;
            background-color: #f8f9fa;
        }

        .signature .title {
            font-weight: bold;
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .signature img {
            max-width: 180px;
            max-height: 70px;
            border: 1px solid #bdc3c7;
            margin-bottom: 10px;
            border-radius: 4px;
            background-color: white;
        }

        .signature .name {
            font-weight: bold;
            color: #2c3e50;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #bdc3c7;
        }

        .signature-placeholder {
            height: 70px;
            background-color: white;
            border: 1px dashed #bdc3c7;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            font-style: italic;
            margin-bottom: 10px;
        }

        .work-order-badge {
            background: linear-gradient(90deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 15px;
        }

        .no-data {
            text-align: center;
            color: #7f8c8d;
            font-style: italic;
            padding: 20px;
        }

        .footer-note {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #bdc3c7;
            font-size: 10px;
            color: #7f8c8d;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Service Report</h1>
        <div class="subtitle">Internal Documentation - {{ $contactInfo['company_name'] }}</div>
    </div>

    <div class="section">
        <div class="work-order-badge">{{ $report->work_order_number }}</div>

        <table class="info-table">
            <tr>
                <th>Work Order</th>
                <td><strong>{{ $report->work_order_number }}</strong></td>
            </tr>
            <tr>
                <th>Customer</th>
                <td>{{ $report->customer_name }}</td>
            </tr>
            <tr>
                <th>Technician</th>
                <td>{{ $report->technician_name }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    @if ($report->client_approve)
                        <span class="status-badge status-signed">Signed</span>
                    @else
                        <span class="status-badge status-pending">Pending Signature</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Close Date</th>
                <td>{{ $report->close_order ? \Carbon\Carbon::parse($report->close_order)->format('d F Y, H:i') : '-' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Survey Identification</div>

        <table class="survey-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Question</th>
                    <th style="width: 60%;">Answer</th>
                </tr>
            </thead>
            <tbody>
                @if ($surveyFields && count($surveyFields) > 0)
                    @foreach ($surveyFields as $field)
                        <tr>
                            <td><strong>{{ $field['label'] ?? $field['id'] }}</strong></td>
                            <td>
                                @php $ans = $surveyAnswers[$field['id']] ?? null; @endphp
                                @if (is_array($ans))
                                    {{ implode(', ', $ans) }}
                                @elseif($ans)
                                    {{ $ans }}
                                @else
                                    <em style="color: #7f8c8d;">No answer provided</em>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2" class="no-data">No survey identification data available.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Digital Signatures</div>

        <div class="signature-section">
            <div class="signature">
                <div class="title">Customer</div>
                @if ($report->client_signature_url)
                    <img src="{{ $report->client_signature_url }}" alt="Customer Signature">
                @else
                    <div class="signature-placeholder">No signature available</div>
                @endif
                <div class="name">{{ $report->client_signature_name ?? 'Not signed' }}</div>
            </div>


            <div class="signature">
                <div class="title">Technician</div>
                @if ($report->technician_signature_url)
                    <img src="{{ $report->technician_signature_url }}" alt="Technician Signature">
                @else
                    <div class="signature-placeholder">No signature available</div>
                @endif
                <div class="name">{{ $report->technician_signature_name ?? 'Not signed' }}</div>
            </div>
        </div>
    </div>

    <div class="footer-note">
        This is an internal service report generated on {{ now()->format('d F Y, H:i') }} WIB<br>
        Document ID: {{ $report->work_order_number ?? 'N/A' }} | {{ $contactInfo['company_name'] }}<br>
        @if (!empty($contactInfo['phone']))
            Phone: {{ $contactInfo['phone'] }}
        @endif
        @if (!empty($contactInfo['email']))
            @if (!empty($contactInfo['phone']))
                |
            @endif
            Email: {{ $contactInfo['email'] }}
        @endif
    </div>
</body>

</html>
