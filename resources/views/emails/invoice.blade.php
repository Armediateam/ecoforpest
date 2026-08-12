<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="format-detection" content="date=no" />
    <meta name="format-detection" content="address=no" />
    <meta name="format-detection" content="email=no" />
    <title>Invoice - {{ $invoice->invoice_number ?? 'Your Invoice' }}</title>
    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <style type="text/css">
        /* Reset styles */
        body,
        table,
        td,
        p,
        a,
        li,
        blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            margin: 0;
            padding: 0;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            border-collapse: collapse;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        /* Body styles */
        body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8f9fa;
            color: #212529;
        }

        /* Container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        /* Header */
        .header-section {
            background: #ffffff;
            text-align: center;
            padding: 40px 30px 30px;
            border-bottom: 2px solid #f1f3f4;
        }

        /* Content */
        .content-section {
            padding: 30px;
            background-color: #ffffff;
        }

        .greeting {
            color: #212529;
            font-size: 18px;
            font-weight: 400;
            margin: 0 0 20px 0;
            line-height: 1.4;
        }

        .intro-text {
            color: #495057;
            font-size: 16px;
            line-height: 1.6;
            margin: 0 0 25px 0;
        }

        /* Custom message */
        .custom-message {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 4px 4px 0;
        }

        .custom-message-title {
            color: #212529;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 8px 0;
        }

        .custom-message-text {
            color: #495057;
            font-size: 15px;
            line-height: 1.5;
            margin: 0;
        }

        /* Invoice details */
        .invoice-details {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin: 25px 0;
            overflow: hidden;
        }

        .invoice-details-title {
            color: #212529;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            padding: 20px 25px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-table td {
            padding: 12px 25px;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
        }

        .invoice-table td:first-child {
            font-weight: 500;
            color: #6c757d;
            width: 35%;
        }

        .invoice-table td:last-child {
            color: #212529;
            font-weight: 400;
        }

        .invoice-table tr:last-child td {
            border-bottom: none;
            padding-bottom: 20px;
        }

        .invoice-amount {
            color: #28a745 !important;
            font-size: 20px !important;
            font-weight: 700 !important;
        }

        .overdue-text {
            color: #dc3545 !important;
            font-weight: 600 !important;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-draft {
            background: #f8f9fa;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }

        .status-sent {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-paid {
            background: #e8f5e8;
            color: #28a745;
        }

        .status-overdue {
            background: #ffebee;
            color: #d32f2f;
        }

        /* Urgent notice */
        .urgent-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 4px 4px 0;
        }

        .urgent-notice-title {
            color: #856404;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 8px 0;
        }

        .urgent-notice-text {
            color: #856404;
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
        }

        /* Payment info */
        .payment-section {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin: 25px 0;
            overflow: hidden;
        }

        .payment-title {
            color: #212529;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            padding: 20px 25px 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .payment-content {
            padding: 20px 25px;
        }

        .bank-account {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 16px;
            margin: 12px 0;
        }

        .bank-name {
            color: #495057;
            font-size: 14px;
            font-weight: 600;
            margin: 0 0 6px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .account-number {
            color: #212529;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            font-size: 18px;
            font-weight: 700;
            background: #ffffff;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            display: inline-block;
            letter-spacing: 1px;
        }

        .payment-instructions {
            margin: 20px 0 0 0;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 6px;
        }

        .payment-instructions-title {
            color: #1565c0;
            font-size: 14px;
            font-weight: 600;
            margin: 0 0 8px 0;
        }

        .contact-info {
            color: #1976d2;
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
        }

        .contact-info strong {
            font-weight: 600;
        }

        /* Footer */
        .footer-section {
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            text-align: center;
            padding: 25px 30px;
            font-size: 13px;
            line-height: 1.5;
        }

        .footer-text {
            margin: 0 0 5px 0;
        }

        .footer-copyright {
            margin: 0;
            color: #adb5bd;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                box-shadow: none !important;
            }

            .header-section,
            .content-section,
            .footer-section {
                padding: 20px 15px !important;
            }

            .payment-content {
                padding: 15px !important;
            }

            .invoice-details-title,
            .payment-title {
                padding: 15px !important;
                font-size: 16px !important;
            }

            .invoice-table td {
                padding: 10px 15px !important;
                font-size: 14px !important;
            }

            .invoice-table td:first-child {
                width: 40% !important;
            }

            .greeting {
                font-size: 16px !important;
            }

            .intro-text {
                font-size: 14px !important;
            }

            .account-number {
                font-size: 16px !important;
                padding: 6px 10px !important;
                letter-spacing: 0.5px !important;
            }

            .bank-account {
                padding: 12px !important;
                margin: 8px 0 !important;
            }

            .custom-message,
            .urgent-notice,
            .payment-instructions {
                padding: 15px !important;
            }
        }

        @media only screen and (max-width: 480px) {
            .invoice-table td {
                display: block !important;
                width: 100% !important;
                padding: 8px 15px !important;
            }

            .invoice-table td:first-child {
                border-bottom: none !important;
                padding-bottom: 4px !important;
                font-weight: 600 !important;
            }

            .invoice-table td:last-child {
                border-bottom: 1px solid #f1f3f4 !important;
                padding-top: 0 !important;
                padding-bottom: 12px !important;
            }

            .invoice-table tr:last-child td:last-child {
                border-bottom: none !important;
                padding-bottom: 20px !important;
            }
        }
    </style>
</head>

<body
    style="height: 100%; margin: 0; padding: 0; width: 100%; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8f9fa; color: #212529;">
    <!-- Wrapper table for Outlook -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
        style="background-color: #f8f9fa;">
        <tr>
            <td align="center" style="padding: 15px;">
                <!-- Main container -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" class="email-container"
                    style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);">

                    <!-- Header -->
                    <tr>
                        <td class="header-section"
                            style="background: #ffffff; text-align: center; padding: 40px 30px 30px; border-bottom: 2px solid #f1f3f4;">
                            <!-- Logo -->
                            <img src="{{ asset('ecoforpest.png') }}" alt="{{ $company }} Logo" width="120"
                                height="auto"
                                style="display: block; margin: 0 auto; max-width: 140px; height: auto;" />
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td class="content-section" style="padding: 30px; background-color: #ffffff;">
                            <h2 class="greeting"
                                style="color: #212529; font-size: 18px; font-weight: 400; margin: 0 0 20px 0; line-height: 1.4;">
                                Dear {{ $customer ? $customer->name : ($lead ? $lead->name : 'Valued Customer') }},
                            </h2>

                            <p class="intro-text"
                                style="color: #495057; font-size: 16px; line-height: 1.6; margin: 0 0 25px 0;">Thank you
                                for your business! Please find your invoice details below and process payment at your
                                earliest convenience.</p>

                            @if ($customMessage)
                                <!-- Custom Message -->
                                <div class="custom-message"
                                    style="background: #f8f9fa; border-left: 4px solid #007bff; padding: 20px; margin: 25px 0; border-radius: 0 4px 4px 0;">
                                    <h3 class="custom-message-title"
                                        style="color: #212529; font-size: 16px; font-weight: 600; margin: 0 0 8px 0;">
                                        Personal Message</h3>
                                    <div class="custom-message-text"
                                        style="color: #495057; font-size: 15px; line-height: 1.5; margin: 0;">
                                        {!! $customMessage !!}
                                    </div>
                                </div>
                            @endif

                            <!-- Invoice Details -->
                            <div class="invoice-details"
                                style="background: #ffffff; border: 1px solid #dee2e6; border-radius: 8px; margin: 25px 0; overflow: hidden;">
                                <h3 class="invoice-details-title"
                                    style="color: #212529; font-size: 18px; font-weight: 600; margin: 0; padding: 20px 25px 15px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                                    Invoice Details</h3>

                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                    class="invoice-table">
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; width: 35%; vertical-align: middle;">
                                            Invoice Number</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; color: #212529; font-weight: 400; vertical-align: middle;">
                                            {{ $invoice->invoice_number ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                            Invoice Date</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; color: #212529; font-weight: 400; vertical-align: middle;">
                                            {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d F Y') : 'N/A' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                            Due Date</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; {{ \Carbon\Carbon::parse($invoice->invoice_due_date ?? now())->isPast() ? 'color: #dc3545; font-weight: 600;' : 'color: #212529; font-weight: 400;' }} vertical-align: middle;">
                                            {{ $invoice->invoice_due_date ? \Carbon\Carbon::parse($invoice->invoice_due_date)->format('d F Y') : 'N/A' }}
                                            @if (\Carbon\Carbon::parse($invoice->invoice_due_date ?? now())->isPast())
                                                <span class="overdue-text"
                                                    style="color: #dc3545; font-weight: 600;">(OVERDUE)</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                            Status</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; color: #212529; font-weight: 400; vertical-align: middle;">
                                            <span
                                                class="status-badge status-{{ strtolower($invoice->status ?? 'draft') }}"
                                                style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; 
                                                @if (strtolower($invoice->status ?? 'draft') == 'draft') background: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6;
                                                @elseif(strtolower($invoice->status ?? 'draft') == 'sent') background: #e3f2fd; color: #1976d2;
                                                @elseif(strtolower($invoice->status ?? 'draft') == 'paid') background: #e8f5e8; color: #28a745;
                                                @else background: #ffebee; color: #d32f2f; @endif">{{ $invoice->status ?? 'Draft' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; padding-bottom: 20px; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                            Total
                                            Amount</td>
                                        <td style="padding: 12px 25px; padding-bottom: 20px; vertical-align: middle;">
                                            <span class="invoice-amount"
                                                style="color: #28a745; font-size: 20px; font-weight: 700;">Rp
                                                {{ number_format($invoice->total ?? 0, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            @if (\Carbon\Carbon::parse($invoice->invoice_due_date ?? now())->isPast())
                                <!-- Urgent Notice -->
                                <div class="urgent-notice"
                                    style="background: #fff3cd; border: 1px solid #ffeaa7; border-left: 4px solid #ffc107; padding: 20px; margin: 25px 0; border-radius: 0 4px 4px 0;">
                                    <h3 class="urgent-notice-title"
                                        style="color: #856404; font-size: 16px; font-weight: 600; margin: 0 0 8px 0;">
                                        ⚠ URGENT NOTICE</h3>
                                    <p class="urgent-notice-text"
                                        style="color: #856404; font-size: 14px; line-height: 1.5; margin: 0;">
                                        This invoice is past due. Please arrange payment immediately to avoid
                                        any service interruption.</p>
                                </div>
                            @endif

                            @include('components.payment-button')

                            <!-- Payment Information -->
                            <div class="payment-section"
                                style="background: #ffffff; border: 1px solid #dee2e6; border-radius: 8px; margin: 25px 0; overflow: hidden;">
                                <h3 class="payment-title"
                                    style="color: #212529; font-size: 18px; font-weight: 600; margin: 0; padding: 20px 25px 10px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                                    Payment Information</h3>
                                <div class="payment-content" style="padding: 20px 25px;">
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

                                        // Fallback to contact info bank accounts if no allowed methods or banks data
                                        if (
                                            empty($allowedBanks) &&
                                            !empty($contactInfo['bank_accounts']) &&
                                            is_array($contactInfo['bank_accounts'])
                                        ) {
                                            $allowedBanks = $contactInfo['bank_accounts'];
                                        }
                                    @endphp

                                    @if (!empty($allowedBanks))
                                        @foreach ($allowedBanks as $bankName => $accountNumber)
                                            @if (is_array($accountNumber))
                                                <!-- For contact info format: ['bank_name' => '...', 'account_holder' => '...', 'account_number' => '...'] -->
                                                <div class="bank-account"
                                                    style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 16px; margin: 12px 0;">
                                                    <div class="bank-name"
                                                        style="color: #495057; font-size: 14px; font-weight: 600; margin: 0 0 6px 0; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        {{ $accountNumber['bank_name'] ?? '' }}{{ !empty($accountNumber['account_holder']) ? ' (' . $accountNumber['account_holder'] . ')' : '' }}
                                                    </div>
                                                    <div class="account-number"
                                                        style="color: #212529; font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace; font-size: 18px; font-weight: 700; background: #ffffff; padding: 8px 12px; border-radius: 4px; border: 1px solid #dee2e6; display: inline-block; letter-spacing: 1px;">
                                                        {{ $accountNumber['account_number'] ?? '' }}</div>
                                                </div>
                                            @else
                                                <!-- For settings format: ['Bank Name' => 'Account Number'] -->
                                                @if ($bankName !== 'Tunai')
                                                    <div class="bank-account"
                                                        style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 16px; margin: 12px 0;">
                                                        <div class="bank-name"
                                                            style="color: #495057; font-size: 14px; font-weight: 600; margin: 0 0 6px 0; text-transform: uppercase; letter-spacing: 0.5px;">
                                                            {{ $bankName }}
                                                        </div>
                                                        <div class="account-number"
                                                            style="color: #212529; font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace; font-size: 18px; font-weight: 700; background: #ffffff; padding: 8px 12px; border-radius: 4px; border: 1px solid #dee2e6; display: inline-block; letter-spacing: 1px;">
                                                            {{ $accountNumber }}</div>
                                                    </div>
                                                @else
                                                    <!-- Special styling for cash payment -->
                                                    <div class="bank-account"
                                                        style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 16px; margin: 12px 0;">
                                                        <div class="bank-name"
                                                            style="color: #856404; font-size: 14px; font-weight: 600; margin: 0 0 6px 0; text-transform: uppercase; letter-spacing: 0.5px;">
                                                            {{ $bankName }}
                                                        </div>
                                                        <div class="account-number"
                                                            style="color: #856404; font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace; font-size: 14px; font-weight: 600; background: #ffffff; padding: 8px 12px; border-radius: 4px; border: 1px solid #ffc107; display: inline-block;">
                                                            {{ $accountNumber }}</div>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                    @else
                                        {{-- warning --}}
                                        <div class="alert alert-warning" role="alert">
                                            No valid bank account information available. Please contact support.
                                        </div>
                                    @endif

                                    <!-- Payment Instructions -->
                                    <div class="payment-instructions"
                                        style="margin: 20px 0 0 0; padding: 15px; background: #e3f2fd; border-radius: 6px;">
                                        <div class="payment-instructions-title"
                                            style="color: #1565c0; font-size: 14px; font-weight: 600; margin: 0 0 8px 0;">
                                            After making payment, please send proof to:</div>
                                        <div class="contact-info"
                                            style="color: #1976d2; font-size: 14px; line-height: 1.5; margin: 0;">
                                            Email: <strong
                                                style="font-weight: 600;">{{ $contactInfo['email'] }}</strong><br>
                                            Phone: <strong
                                                style="font-weight: 600;">{{ $contactInfo['phone'] }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p style="color: #495057; font-size: 15px; line-height: 1.6; margin: 25px 0 15px 0;">The
                                complete invoice document is attached to this email. If you have any questions, please
                                contact us immediately.</p>

                            <p style="color: #212529; font-size: 16px; line-height: 1.6; margin: 20px 0 0 0;">Best
                                regards,<br><strong>{{ $company }}</strong></p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer-section"
                            style="background: #f8f9fa; border-top: 1px solid #dee2e6; color: #6c757d; text-align: center; padding: 25px 30px; font-size: 13px; line-height: 1.5;">
                            <p class="footer-text" style="margin: 0 0 5px 0;">This email was sent automatically by
                                {{ $company }} billing system.</p>
                            <p class="footer-copyright" style="margin: 0; color: #adb5bd;">&copy; {{ date('Y') }}
                                {{ $company }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
