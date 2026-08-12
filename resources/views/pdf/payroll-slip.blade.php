@php
    use App\Helpers\SettingsHelper;
    $contactInfo = SettingsHelper::getEmailContactInfo();
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - {{ $payroll->employee->name }}</title>
    <style>
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
            line-height: 1.4;
        }

        .payroll-box {
            max-width: 190mm;
            margin: 0 auto;
            padding: 5mm;
            background: white;
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 3px solid #007c42;
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
            max-width: 200px;
            max-height: 80px;
            height: auto;
        }

        .company-info {
            margin-top: 4px;
            font-size: 8px;
            color: #666;
            line-height: 1.2;
        }

        .slip-title {
            font-size: 18px;
            font-weight: bold;
            color: #007c42;
            margin: 0 0 6px 0;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .period-info {
            font-size: 11px;
            color: #333;
            margin-bottom: 6px;
        }

        .period-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background-color: #007c42;
            color: white;
        }

        /* Employee Information */
        .employee-info {
            width: 100%;
            margin: 15px 0;
            border: none;
            background: transparent;
        }

        .employee-grid {
            display: table;
            width: 100%;
            border-spacing: 0;
        }

        .employee-row {
            display: table-row;
        }

        .employee-cell {
            display: table-cell;
            width: 25%;
            padding: 8px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #e5e7eb;
        }

        .employee-cell:first-child {
            padding-left: 0;
        }

        .employee-cell:last-child {
            padding-right: 0;
        }

        .info-label {
            font-size: 8px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            display: block;
        }

        .info-value {
            font-weight: 600;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.3;
        }

        /* Attendance Summary */
        .attendance-section {
            margin: 10px 0;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #007c42;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 2px solid #007c42;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .attendance-grid {
            display: table;
            width: 100%;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .attendance-item {
            display: table-cell;
            width: 20%;
            padding: 6px 4px;
            text-align: center;
            border-right: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .attendance-item:last-child {
            border-right: none;
        }

        .attendance-label {
            font-size: 7px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 2px;
            display: block;
            line-height: 1;
        }

        .attendance-value {
            font-size: 11px;
            font-weight: bold;
            color: #007c42;
            line-height: 1;
        }

        .attendance-unit {
            font-size: 6px;
            color: #666;
            margin-top: 1px;
            line-height: 1;
        }

        /* Income and Expense Tables */
        .components-container {
            display: table;
            width: 100%;
            margin: 10px 0;
        }

        .components-left,
        .components-right {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding-right: 2%;
        }

        .components-right {
            padding-right: 0;
            padding-left: 2%;
        }

        .components-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            background-color: white;
            border: 1px solid #dee2e6;
        }

        .components-table thead {
            background-color: #007c42 !important;
            color: white !important;
        }

        .components-table thead th {
            padding: 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #007c42;
            background-color: #007c42 !important;
            color: white !important;
        }

        .components-table tbody tr {
            border-bottom: 1px solid #e9ecef;
        }

        .components-table tbody tr:last-child {
            border-bottom: 2px solid #007c42;
        }

        .components-table tbody td {
            padding: 6px;
            vertical-align: top;
            font-size: 9px;
            border-right: 1px solid #f0f0f0;
        }

        .components-table tbody td:last-child {
            border-right: none;
        }

        .component-name {
            font-weight: 600;
            color: #333;
        }

        .component-amount {
            text-align: right;
            font-weight: 600;
            color: #333;
            font-family: 'Courier New', monospace;
        }

        .income-amount {
            color: #28a745;
        }

        .expense-amount {
            color: #dc3545;
        }

        /* Summary Section */
        .summary-section {
            margin: 15px 0;
            background-color: #f8f9fa;
            border: 2px solid #007c42;
            border-radius: 8px;
            padding: 12px;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 0 6px;
            vertical-align: top;
        }

        .summary-label {
            font-size: 10px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
            display: block;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .gross-income {
            color: #28a745;
        }

        .total-deductions {
            color: #dc3545;
        }

        .net-salary {
            color: #007c42;
            font-size: 16px;
            background-color: white;
            padding: 6px;
            border: 2px solid #007c42;
            border-radius: 5px;
        }

        /* Footer */
        .footer-section {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #dee2e6;
            font-size: 8px;
            color: #666;
        }

        .signature-section {
            display: table;
            width: 100%;
            margin-top: 15px;
        }

        .signature-left,
        .signature-right {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 20px 15px;
        }

        .signature-title {
            font-size: 11px;
            font-weight: bold;
            color: #333;
            margin-bottom: 60px;
            text-transform: uppercase;
        }

        .signature-name {
            font-size: 11px;
            font-weight: bold;
            color: #333;
            border-top: 1px solid #333;
            padding-top: 8px;
            display: inline-block;
            min-width: 180px;
        }

        /* Print Optimizations */
        @media print {
            body {
                font-size: 10px;
                margin: 0;
                padding: 0;
                background: white;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .payroll-box {
                max-width: none;
                width: 100%;
                margin: 0;
                padding: 5mm;
                box-shadow: none;
                border: none;
            }

            .components-table thead {
                display: table-header-group;
                page-break-after: avoid;
                background-color: #007c42 !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .components-table thead th {
                background-color: #007c42 !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .summary-section {
                page-break-inside: avoid;
            }

            .no-break {
                page-break-inside: avoid;
            }
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .currency {
            font-family: 'Courier New', monospace;
        }

        .no-break {
            page-break-inside: avoid;
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
    <div class="payroll-box">
        <!-- Header Section -->
        <div class="header no-break">
            <div class="header-left">
                <img src="{{ $logoBase64 }}" alt="Ecoforpest Logo" class="logo" />
                <div class="company-info">
                    <div><strong>{{ $contactInfo['company_name'] }}</strong></div>
                    <div>{{ $contactInfo['address'] }}</div>
                    <div><strong>NPWP:</strong> {{ $contactInfo['npwp'] }}</div>
                </div>
            </div>
            <div class="header-right">
                <h1 class="slip-title">Slip Gaji</h1>
                <div class="period-info">
                    <strong>Periode:</strong> {{ $payroll->period_start_date->format('d M Y') }} -
                    {{ $payroll->period_end_date->format('d M Y') }}
                </div>
                <div class="period-badge">
                    {{ $payroll->period_start_date->format('F Y') }}
                </div>
            </div>
        </div>

        <!-- Employee Information -->
        <div class="employee-info no-break">
            <div class="employee-grid">
                <div class="employee-row">
                    <div class="employee-cell">
                        <span class="info-label">Nama Karyawan</span>
                        <div class="info-value">{{ $payroll->employee->name }}</div>
                    </div>
                    <div class="employee-cell">
                        <span class="info-label">ID Karyawan</span>
                        <div class="info-value">{{ $payroll->employee->employee_id ?? '-' }}</div>
                    </div>
                    <div class="employee-cell">
                        <span class="info-label">Departemen</span>
                        <div class="info-value">{{ $payroll->employee->position->department->name ?? '-' }}</div>
                    </div>
                    <div class="employee-cell">
                        <span class="info-label">Posisi</span>
                        <div class="info-value">{{ $payroll->employee->position->title ?? '-' }}</div>
                    </div>
                </div>
                <div class="employee-row">
                    <div class="employee-cell">
                        <span class="info-label">Tanggal Bergabung</span>
                        <div class="info-value">
                            {{ $payroll->employee->hire_date ? $payroll->employee->hire_date->format('d M Y') : '-' }}
                        </div>
                    </div>
                    <div class="employee-cell">
                        <span class="info-label">Status</span>
                        <div class="info-value">{{ ucfirst($payroll->employee->status ?? 'Active') }}</div>
                    </div>
                    <div class="employee-cell">
                        <span class="info-label">Periode Gaji</span>
                        <div class="info-value">{{ $payroll->period_start_date->format('M Y') }}</div>
                    </div>
                    <div class="employee-cell">
                        <span class="info-label">Slip No.</span>
                        <div class="info-value">#{{ str_pad($payroll->id, 6, '0', STR_PAD_LEFT) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Summary -->
        <div class="attendance-section no-break">
            <div class="section-title">Ringkasan Kehadiran</div>
            <div class="attendance-grid">
                <div class="attendance-item">
                    <span class="attendance-label">Hari Kerja</span>
                    <div class="attendance-value">{{ $payroll->work_days }}</div>
                    <div class="attendance-unit">hari</div>
                </div>
                <div class="attendance-item">
                    <span class="attendance-label">Cuti</span>
                    <div class="attendance-value">{{ $payroll->leave_days }}</div>
                    <div class="attendance-unit">hari</div>
                </div>
                <div class="attendance-item">
                    <span class="attendance-label">Izin</span>
                    <div class="attendance-value">{{ $payroll->permission_days }}</div>
                    <div class="attendance-unit">hari</div>
                </div>
                <div class="attendance-item">
                    <span class="attendance-label">Absen</span>
                    <div class="attendance-value">{{ $payroll->absent_days }}</div>
                    <div class="attendance-unit">hari</div>
                </div>
                <div class="attendance-item">
                    <span class="attendance-label">Lembur</span>
                    <div class="attendance-value">{{ $payroll->overtime_hours }}</div>
                    <div class="attendance-unit">jam</div>
                </div>
            </div>
        </div>

        <!-- Income and Expense Components -->
        @php
            // Calculate income rows
            $incomes = $payroll->employee_income ?? [];
            $totalIncome = 0;
            $incomeRows = [];

            $isIncomeRepeaterFormat =
                isset($incomes[0]) && is_array($incomes[0]) && isset($incomes[0]['name'], $incomes[0]['nominal']);

            if ($isIncomeRepeaterFormat) {
                foreach ($incomes as $income) {
                    if (
                        isset($income['name'], $income['nominal']) &&
                        is_numeric($income['nominal'])
                    ) {
                        $totalIncome += $income['nominal'];
                        $incomeRows[] = $income;
                    }
                }
            } else {
                foreach ($incomes as $key => $value) {
                    if ($key !== 'total_penghasilan' && is_numeric($value)) {
                        $totalIncome += $value;
                        $incomeRows[] = ['name' => ucwords(str_replace('_', ' ', $key)), 'nominal' => $value];
                    }
                }
            }

            // Calculate expense rows
            $expenses = $payroll->employee_expense ?? [];
            $totalExpense = 0;
            $expenseRows = [];

            $isExpenseRepeaterFormat =
                isset($expenses[0]) && is_array($expenses[0]) && isset($expenses[0]['name'], $expenses[0]['nominal']);

            if ($isExpenseRepeaterFormat) {
                foreach ($expenses as $expense) {
                    if (
                        isset($expense['name'], $expense['nominal']) &&
                        is_numeric($expense['nominal'])
                    ) {
                        $totalExpense += $expense['nominal'];
                        $expenseRows[] = $expense;
                    }
                }
            } else {
                foreach ($expenses as $key => $value) {
                    if ($key !== 'total_potongan' && is_numeric($value)) {
                        $totalExpense += $value;
                        $expenseRows[] = ['name' => ucwords(str_replace('_', ' ', $key)), 'nominal' => $value];
                    }
                }
            }

            // Calculate max rows needed
            $maxRows = max(count($incomeRows), count($expenseRows));
        @endphp

        <div class="components-container no-break">
            <!-- Income Components -->
            <div class="components-left">
                <div class="section-title">Komponen Pendapatan</div>
                <table class="components-table">
                    <thead>
                        <tr>
                            <th style="width: 60%;">Keterangan</th>
                            <th style="width: 40%;" class="text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (count($incomeRows) > 0)
                            @foreach ($incomeRows as $income)
                                <tr>
                                    <td class="component-name">{{ $income['name'] }}</td>
                                    <td class="component-amount income-amount">Rp
                                        {{ number_format($income['nominal'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Add empty rows to match max rows --}}
                            @for ($i = count($incomeRows); $i < $maxRows; $i++)
                                <tr>
                                    <td class="component-name" style="color: #ccc;">-</td>
                                    <td class="component-amount" style="color: #ccc;">-</td>
                                </tr>
                            @endfor
                        @else
                            {{-- If no income data, create empty rows --}}
                            @for ($i = 0; $i < $maxRows; $i++)
                                <tr>
                                    <td class="component-name" style="color: #ccc;">-</td>
                                    <td class="component-amount" style="color: #ccc;">-</td>
                                </tr>
                            @endfor
                        @endif

                        <tr style="background-color: #e8f5e8; font-weight: bold;">
                            <td class="component-name">TOTAL PENDAPATAN</td>
                            <td class="component-amount income-amount">Rp
                                {{ number_format($totalIncome, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Expense Components -->
            <div class="components-right">
                <div class="section-title">Komponen Potongan</div>
                <table class="components-table">
                    <thead>
                        <tr>
                            <th style="width: 60%;">Keterangan</th>
                            <th style="width: 40%;" class="text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (count($expenseRows) > 0)
                            @foreach ($expenseRows as $expense)
                                <tr>
                                    <td class="component-name">{{ $expense['name'] }}</td>
                                    <td class="component-amount expense-amount">Rp
                                        {{ number_format($expense['nominal'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Add empty rows to match max rows --}}
                            @for ($i = count($expenseRows); $i < $maxRows; $i++)
                                <tr>
                                    <td class="component-name" style="color: #ccc;">-</td>
                                    <td class="component-amount" style="color: #ccc;">-</td>
                                </tr>
                            @endfor
                        @else
                            {{-- If no expense data, create empty rows --}}
                            @for ($i = 0; $i < $maxRows; $i++)
                                <tr>
                                    <td class="component-name" style="color: #ccc;">-</td>
                                    <td class="component-amount" style="color: #ccc;">-</td>
                                </tr>
                            @endfor
                        @endif

                        <tr style="background-color: #f8e8e8; font-weight: bold;">
                            <td class="component-name">TOTAL POTONGAN</td>
                            <td class="component-amount expense-amount">Rp
                                {{ number_format($totalExpense, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="summary-section no-break">
            <div class="section-title" style="border-bottom: none; margin-bottom: 10px;">Ringkasan Gaji</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-label">Total Pendapatan</span>
                    <div class="summary-value gross-income currency">
                        Rp {{ number_format($totalIncome ?? 0, 0, ',', '.') }}
                    </div>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Potongan</span>
                    <div class="summary-value total-deductions currency">
                        Rp {{ number_format($totalExpense ?? 0, 0, ',', '.') }}
                    </div>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Gaji Bersih</span>
                    <div class="summary-value net-salary currency">
                        Rp {{ number_format($payroll->final_salary, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="signature-section no-break">
            <div class="signature-left">
                <div class="signature-title">Karyawan</div>
                <div class="signature-name">{{ $payroll->employee->name }}</div>
            </div>
            <div class="signature-right">
                <div class="signature-title">HRD / Finance</div>
                <div class="signature-name">{{ $payroll->generatedBy->name ?? 'System' }}</div>
            </div>
        </div>

        <!-- Footer Information -->
        <div class="footer-section no-break">
            <div style="text-align: center;">
                <p style="margin: 0; font-size: 8px;">
                    Slip gaji ini dibuat secara elektronik dan sah tanpa tanda tangan basah.
                </p>
                <p style="margin: 2px 0 0 0; font-size: 8px;">
                    Dicetak pada: {{ now()->format('d F Y, H:i:s') }} •
                    Dibuat oleh: {{ $payroll->generatedBy->name ?? 'System' }}
                </p>
            </div>
        </div>
    </div>
</body>

</html>
