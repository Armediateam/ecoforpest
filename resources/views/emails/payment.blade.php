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
    <title>Payment Receipt - {{ $payment->invoice->invoice_number ?? 'Payment Confirmation' }}</title>
    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
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

        /* Success badge */
        .success-badge {
            background: #e8f5e8;
            border: 2px solid #28a745;
            border-radius: 50px;
            color: #155724;
            text-align: center;
            padding: 16px 24px;
            margin: 25px auto;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            max-width: 280px;
            display: inline-block;
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
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 4px 4px 0;
        }

        .custom-message-title {
            color: #28a745;
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

        /* Payment details */
        .payment-details {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin: 25px 0;
            overflow: hidden;
        }

        .payment-details-title {
            color: #212529;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            padding: 20px 25px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-table td {
            padding: 12px 25px;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
        }

        .payment-table td:first-child {
            font-weight: 500;
            color: #6c757d;
            width: 35%;
        }

        .payment-table td:last-child {
            color: #212529;
            font-weight: 400;
        }

        .payment-table tr:last-child td {
            border-bottom: none;
            padding-bottom: 20px;
        }

        .payment-amount {
            color: #28a745 !important;
            font-size: 20px !important;
            font-weight: 700 !important;
        }

        /* Invoice summary */
        .invoice-summary {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin: 25px 0;
            overflow: hidden;
        }

        .summary-title {
            color: #212529;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            padding: 20px 25px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .balance-paid {
            color: #28a745 !important;
            font-weight: 600 !important;
        }

        .balance-remaining {
            color: #dc3545 !important;
            font-weight: 600 !important;
        }

        /* Company info */
        .company-info {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin: 25px 0;
            overflow: hidden;
        }

        .company-info-title {
            color: #212529;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            padding: 20px 25px 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .company-content {
            padding: 20px 25px;
        }

        .company-details {
            color: #495057;
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
        }

        .company-details strong {
            color: #212529;
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

            .company-content {
                padding: 15px !important;
            }

            .payment-details-title,
            .summary-title,
            .company-info-title {
                padding: 15px !important;
                font-size: 16px !important;
            }

            .payment-table td {
                padding: 10px 15px !important;
                font-size: 14px !important;
            }

            .payment-table td:first-child {
                width: 40% !important;
            }

            .greeting {
                font-size: 16px !important;
            }

            .intro-text {
                font-size: 14px !important;
            }

            .success-badge {
                font-size: 14px !important;
                padding: 12px 20px !important;
                letter-spacing: 0.3px !important;
            }

            .custom-message {
                padding: 15px !important;
            }
        }

        @media only screen and (max-width: 480px) {
            .payment-table td {
                display: block !important;
                width: 100% !important;
                padding: 8px 15px !important;
            }

            .payment-table td:first-child {
                border-bottom: none !important;
                padding-bottom: 4px !important;
                font-weight: 600 !important;
            }

            .payment-table td:last-child {
                border-bottom: 1px solid #f1f3f4 !important;
                padding-top: 0 !important;
                padding-bottom: 12px !important;
            }

            .payment-table tr:last-child td:last-child {
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
                                Dear {{ $customer->name ?? 'Valued Customer' }},</h2>

                            <!-- Success Badge -->
                            <div style="text-align: center;">
                                <div class="success-badge"
                                    style="background: #e8f5e8; border: 2px solid #28a745; border-radius: 50px; color: #155724; text-align: center; padding: 16px 24px; margin: 25px auto; font-size: 16px; font-weight: 600; letter-spacing: 0.5px; max-width: 280px; display: inline-block;">
                                    ✅ PAYMENT RECEIVED
                                </div>
                            </div>

                            <p class="intro-text"
                                style="color: #495057; font-size: 16px; line-height: 1.6; margin: 0 0 25px 0;">Thank you
                                for your payment! This email confirms that we have successfully received your payment.
                                Your transaction has been processed and recorded in our system.</p>

                            @if ($customMessage)
                                <!-- Custom Message -->
                                <div class="custom-message"
                                    style="background: #f8f9fa; border-left: 4px solid #28a745; padding: 20px; margin: 25px 0; border-radius: 0 4px 4px 0;">
                                    <h3 class="custom-message-title"
                                        style="color: #28a745; font-size: 16px; font-weight: 600; margin: 0 0 8px 0;">
                                        Personal Message</h3>
                                    <div class="custom-message-text"
                                        style="color: #495057; font-size: 15px; line-height: 1.5; margin: 0;">
                                        {!! nl2br(e($customMessage)) !!}</div>
                                </div>
                            @endif

                            <!-- Payment Details -->
                            <div class="payment-details"
                                style="background: #ffffff; border: 1px solid #dee2e6; border-radius: 8px; margin: 25px 0; overflow: hidden;">
                                <h3 class="payment-details-title"
                                    style="color: #212529; font-size: 18px; font-weight: 600; margin: 0; padding: 20px 25px 15px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                                    Payment Details</h3>

                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                    class="payment-table">
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; width: 35%; vertical-align: middle;">
                                            Receipt Number</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; color: #212529; font-weight: 400; vertical-align: middle;">
                                            {{ $payment->id ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                            Invoice Number</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; color: #212529; font-weight: 400; vertical-align: middle;">
                                            {{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                            Payment Date</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; color: #212529; font-weight: 400; vertical-align: middle;">
                                            {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d F Y') : 'N/A' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                            Payment Method</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; color: #212529; font-weight: 400; vertical-align: middle;">
                                            {{ $payment->payment_mode ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; padding-bottom: 20px; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                            Amount
                                            Paid</td>
                                        <td style="padding: 12px 25px; padding-bottom: 20px; vertical-align: middle;">
                                            <span class="payment-amount"
                                                style="color: #28a745; font-size: 20px; font-weight: 700;">Rp
                                                {{ number_format($payment->amount ?? 0, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                    @if ($payment->notes)
                                        <tr>
                                            <td
                                                style="padding: 12px 25px; padding-bottom: 20px; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                                Notes</td>
                                            <td
                                                style="padding: 12px 25px; padding-bottom: 20px; color: #212529; font-weight: 400; vertical-align: middle;">
                                                {{ $payment->notes }}
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            <!-- Invoice Summary -->
                            <div class="invoice-summary"
                                style="background: #ffffff; border: 1px solid #dee2e6; border-radius: 8px; margin: 25px 0; overflow: hidden;">
                                <h3 class="summary-title"
                                    style="color: #212529; font-size: 18px; font-weight: 600; margin: 0; padding: 20px 25px 15px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                                    Invoice Summary</h3>

                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                    class="payment-table">
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; width: 35%; vertical-align: middle;">
                                            Original Invoice Amount</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; color: #212529; font-weight: 400; vertical-align: middle;">
                                            Rp {{ number_format($payment->invoice->total ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                            Amount Paid</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; color: #212529; font-weight: 400; vertical-align: middle;">
                                            Rp {{ number_format($payment->amount ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr style="border-top: 2px solid #34495e;">
                                        <td
                                            style="padding: 15px 25px 20px; font-weight: 600; color: #212529; vertical-align: middle;">
                                            Remaining Balance</td>
                                        <td style="padding: 15px 25px 20px; font-weight: 600; vertical-align: middle;">
                                            @php
                                                $remaining = ($payment->invoice->total ?? 0) - ($payment->amount ?? 0);
                                            @endphp
                                            @if ($remaining > 0)
                                                <span class="balance-remaining"
                                                    style="color: #dc3545; font-weight: 600;">Rp
                                                    {{ number_format($remaining, 0, ',', '.') }}</span>
                                            @else
                                                <span class="balance-paid"
                                                    style="color: #28a745; font-weight: 600;">PAID IN
                                                    FULL</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Company Information -->
                            <div class="company-info"
                                style="background: #ffffff; border: 1px solid #dee2e6; border-radius: 8px; margin: 25px 0; overflow: hidden;">
                                <h3 class="company-info-title"
                                    style="color: #212529; font-size: 18px; font-weight: 600; margin: 0; padding: 20px 25px 10px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                                    Contact Information</h3>
                                <div class="company-content" style="padding: 20px 25px;">
                                    <div class="company-details"
                                        style="color: #495057; font-size: 15px; line-height: 1.6; margin: 0;">
                                        <strong
                                            style="color: #212529; font-weight: 600;">{{ $contactInfo['company_name'] }}</strong><br>
                                        {{ $contactInfo['address'] }}<br>
                                        <strong style="color: #212529; font-weight: 600;">Email:</strong>
                                        {{ $contactInfo['email'] }}<br>
                                        <strong style="color: #212529; font-weight: 600;">Phone:</strong>
                                        {{ $contactInfo['phone'] }}
                                    </div>
                                </div>
                            </div>

                            <p style="color: #495057; font-size: 15px; line-height: 1.6; margin: 25px 0 15px 0;">The
                                official payment receipt is attached to this email for your records. Please keep this
                                receipt for your files.</p>

                            <p style="color: #495057; font-size: 15px; line-height: 1.6; margin: 0 0 15px 0;">If you
                                have any questions about this payment or need additional documentation, please don't
                                hesitate to contact us.</p>

                            <p style="color: #212529; font-size: 16px; line-height: 1.6; margin: 20px 0 0 0;">Best
                                regards,<br><strong>{{ $company }} Accounting Team</strong></p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer-section"
                            style="background: #f8f9fa; border-top: 1px solid #dee2e6; color: #6c757d; text-align: center; padding: 25px 30px; font-size: 13px; line-height: 1.5;">
                            <p class="footer-text" style="margin: 0 0 5px 0;">This email was sent automatically by
                                {{ $company }} payment system.</p>
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
