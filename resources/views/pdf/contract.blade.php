@php
    use App\Helpers\SettingsHelper;
    $contactInfo = SettingsHelper::getEmailContactInfo();
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $record->subject }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .company-details p,
        .customer-details p {
            margin: 0;
            line-height: 1.5;
        }

        .customer-details {
            text-align: right;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #34D399;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header h2 {
            margin: 5px 0 0;
            font-size: 18px;
            font-weight: normal;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            background-color: #f0f0f0;
            padding: 8px;
            font-size: 16px;
            font-weight: bold;
            border-left: 5px solid #34D399;
            margin-bottom: 10px;
        }

        .details-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .details-row {
            display: table-row;
        }

        .details-label,
        .details-value {
            display: table-cell;
            padding: 8px 15px;
            border-bottom: 1px solid #ddd;
        }

        .details-label {
            font-weight: bold;
            width: 30%;
            background-color: #f9f9f9;
        }

        .details-value {
            width: 70%;
        }

        .company-logo {
            max-width: 240px;
            max-height: 120px;
            height: auto;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .header-table td {
            vertical-align: top;
            width: 50%;
        }
    </style>
</head>

<body>
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
                    <p><strong>{{ $record->customer->name }}</strong></p>
                    <p>{{ $record->customer->address }}</p>
                    <p>{{ $record->customer->zip_code }}</p>
                </div>
            </td>
        </tr>
    </table>
    <div class="header">
        <h1>{{ $record->subject }}</h1>
    </div>

    <div class="section">
        <div class="section-title">Informasi Dasar</div>
        <div class="details-grid">
            <div class="details-row">
                <div class="details-label">Pelanggan</div>
                <div class="details-value">{{ $record->customer->name ?? '-' }}</div>
            </div>
            <div class="details-row">
                <div class="details-label">Tipe Kontrak</div>
                <div class="details-value">{{ $record->contractType->name ?? '-' }}</div>
            </div>
            <div class="details-row">
                <div class="details-label">Template Kontrak</div>
                <div class="details-value">{{ $record->contractTemplate->name ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Detail Keuangan & Pekerjaan</div>
        <div class="details-grid">
            <div class="details-row">
                <div class="details-label">Nilai Kontrak</div>
                <div class="details-value">
                    Rp. {{ number_format($record->contract_value, 0, ',', '.') }}
                </div>
            </div>
            <div class="details-row">
                <div class="details-label">Total Pengerjaan</div>
                <div class="details-value">{{ $record->total_workmanship }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Jangka Waktu</div>
        <div class="details-grid">
            <div class="details-row">
                <div class="details-label">Tanggal Mulai</div>
                <div class="details-value">
                    {{ $record->start_date ? \Carbon\Carbon::parse($record->start_date)->format('d-m-Y') : '-' }}</div>
            </div>
            <div class="details-row">
                <div class="details-label">Tanggal Berakhir</div>
                <div class="details-value">
                    {{ $record->end_date ? \Carbon\Carbon::parse($record->end_date)->format('d-m-Y') : '-' }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Deskripsi</div>
        {{ $record->description }}
    </div>

</body>

</html>
