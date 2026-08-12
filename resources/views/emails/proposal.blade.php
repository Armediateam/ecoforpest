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
    <title>Proposal - {{ $proposal->subject ?? 'Your Proposal' }}</title>
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
            color: #007bff;
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

        /* Proposal details */
        .proposal-details {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin: 25px 0;
            overflow: hidden;
        }

        .proposal-details-title {
            color: #212529;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            padding: 20px 25px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .proposal-table {
            width: 100%;
            border-collapse: collapse;
        }

        .proposal-table td {
            padding: 12px 25px;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
        }

        .proposal-table td:first-child {
            font-weight: 500;
            color: #6c757d;
            width: 35%;
        }

        .proposal-table td:last-child {
            color: #212529;
            font-weight: 400;
        }

        .proposal-table tr:last-child td {
            border-bottom: none;
            padding-bottom: 20px;
        }

        .proposal-amount {
            color: #007bff !important;
            font-size: 18px !important;
            font-weight: 700 !important;
        }

        /* Call to action */
        .cta-section {
            background: #e3f2fd;
            border-left: 4px solid #007bff;
            border-radius: 0 8px 8px 0;
            padding: 25px;
            margin: 25px 0;
        }

        .cta-title {
            color: #1976d2;
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 12px 0;
        }

        .cta-text {
            color: #1565c0;
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
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

            .proposal-details-title,
            .company-info-title {
                padding: 15px !important;
                font-size: 16px !important;
            }

            .proposal-table td {
                padding: 10px 15px !important;
                font-size: 14px !important;
            }

            .proposal-table td:first-child {
                width: 40% !important;
            }

            .greeting {
                font-size: 16px !important;
            }

            .intro-text {
                font-size: 14px !important;
            }

            .custom-message,
            .cta-section {
                padding: 15px !important;
            }
        }

        @media only screen and (max-width: 480px) {
            .proposal-table td {
                display: block !important;
                width: 100% !important;
                padding: 8px 15px !important;
            }

            .proposal-table td:first-child {
                border-bottom: none !important;
                padding-bottom: 4px !important;
                font-weight: 600 !important;
            }

            .proposal-table td:last-child {
                border-bottom: 1px solid #f1f3f4 !important;
                padding-top: 0 !important;
                padding-bottom: 12px !important;
            }

            .proposal-table tr:last-child td:last-child {
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

                            <p class="intro-text"
                                style="color: #495057; font-size: 16px; line-height: 1.6; margin: 0 0 25px 0;">We are
                                pleased to present you with our comprehensive business proposal. This document outlines
                                our recommended solutions tailored specifically to meet your requirements and business
                                objectives.</p>

                            @if ($customMessage)
                                <!-- Custom Message -->
                                <div class="custom-message"
                                    style="background: #f8f9fa; border-left: 4px solid #007bff; padding: 20px; margin: 25px 0; border-radius: 0 4px 4px 0;">
                                    <h3 class="custom-message-title"
                                        style="color: #007bff; font-size: 16px; font-weight: 600; margin: 0 0 8px 0;">
                                        Personal Message</h3>
                                    <div class="custom-message-text"
                                        style="color: #495057; font-size: 15px; line-height: 1.5; margin: 0;">
                                        {!! nl2br(e($customMessage)) !!}</div>
                                </div>
                            @endif

                            <!-- Proposal Details -->
                            <div class="proposal-details"
                                style="background: #ffffff; border: 1px solid #dee2e6; border-radius: 8px; margin: 25px 0; overflow: hidden;">
                                <h3 class="proposal-details-title"
                                    style="color: #212529; font-size: 18px; font-weight: 600; margin: 0; padding: 20px 25px 15px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                                    Proposal Details</h3>

                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                    class="proposal-table">
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; width: 35%; vertical-align: middle;">
                                            Proposal Number</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; color: #212529; font-weight: 400; vertical-align: middle;">
                                            {{ $proposal->id ?? 'Draft' }}</td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                            Subject</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; color: #212529; font-weight: 400; vertical-align: middle;">
                                            {{ $proposal->subject ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                            Date</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; color: #212529; font-weight: 400; vertical-align: middle;">
                                            {{ $proposal->date ? \Carbon\Carbon::parse($proposal->date)->format('d F Y') : 'N/A' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                            Status</td>
                                        <td
                                            style="padding: 12px 25px; border-bottom: 1px solid #f1f3f4; color: #212529; font-weight: 400; vertical-align: middle; text-transform: capitalize;">
                                            {{ $proposal->status ?? 'Draft' }}</td>
                                    </tr>
                                    @php
                                        $totalAmount = 0;

                                        // Try to get total from different sources
                                        if (isset($proposal->proposalOrder) && $proposal->proposalOrder) {
                                            // First try: direct total property
                                            if (
                                                isset($proposal->proposalOrder->total) &&
                                                $proposal->proposalOrder->total > 0
                                            ) {
                                                $totalAmount = $proposal->proposalOrder->total;
                                            }
                                            // Second try: calculate from subtotal
                                            elseif (
                                                isset($proposal->proposalOrder->subtotal) &&
                                                $proposal->proposalOrder->subtotal > 0
                                            ) {
                                                $subtotal = $proposal->proposalOrder->subtotal;
                                                $discountFixed = $proposal->proposalOrder->discount_fixed ?? 0;
                                                $discountPercent = $proposal->proposalOrder->discount_percent ?? 0;
                                                $adjustment = $proposal->proposalOrder->adjustment ?? 0;

                                                $discountAmount =
                                                    $discountFixed > 0
                                                        ? $discountFixed
                                                        : $subtotal * ($discountPercent / 100);
                                                $totalAmount = $subtotal - $discountAmount + $adjustment;
                                            }
                                            // Third try: calculate from proposalItems if available and is a collection
                                            elseif (
                                                isset($proposal->proposalOrder->proposalItems) &&
                                                is_object($proposal->proposalOrder->proposalItems) &&
                                                method_exists($proposal->proposalOrder->proposalItems, 'sum')
                                            ) {
                                                try {
                                                    $itemsTotal = $proposal->proposalOrder->proposalItems->sum(
                                                        'amount',
                                                    );
                                                    if ($itemsTotal > 0) {
                                                        $discountFixed = $proposal->proposalOrder->discount_fixed ?? 0;
                                                        $discountPercent =
                                                            $proposal->proposalOrder->discount_percent ?? 0;
                                                        $adjustment = $proposal->proposalOrder->adjustment ?? 0;

                                                        $discountAmount =
                                                            $discountFixed > 0
                                                                ? $discountFixed
                                                                : $itemsTotal * ($discountPercent / 100);
                                                        $totalAmount = $itemsTotal - $discountAmount + $adjustment;
                                                    }
                                                } catch (\Exception $e) {
                                                    // Ignore calculation errors
                                                }
                                            }
                                        }
                                    @endphp
                                    @if ($totalAmount > 0)
                                        <tr>
                                            <td
                                                style="padding: 12px 25px; padding-bottom: 20px; font-weight: 500; color: #6c757d; vertical-align: middle;">
                                                Estimated Value</td>
                                            <td
                                                style="padding: 12px 25px; padding-bottom: 20px; vertical-align: middle;">
                                                <span class="proposal-amount"
                                                    style="color: #007bff; font-size: 18px; font-weight: 700;">Rp
                                                    {{ number_format($totalAmount, 0, ',', '.') }}</span>
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            <!-- Call to Action -->
                            <div class="cta-section"
                                style="background: #e3f2fd; border-left: 4px solid #007bff; border-radius: 0 8px 8px 0; padding: 25px; margin: 25px 0;">
                                <h3 class="cta-title"
                                    style="color: #1976d2; font-size: 18px; font-weight: 600; margin: 0 0 12px 0;">
                                    Next Steps</h3>
                                <p class="cta-text"
                                    style="color: #1565c0; font-size: 15px; line-height: 1.6; margin: 0;">The
                                    complete proposal document is attached to this email for your detailed
                                    review. Should you have any questions or require clarification on any aspect
                                    of our proposal, please don't hesitate to contact us.</p>
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

                            <p style="color: #495057; font-size: 15px; line-height: 1.6; margin: 25px 0 15px 0;">We
                                appreciate the opportunity to present this proposal and look forward to the possibility
                                of working together. Our team is committed to delivering exceptional results that exceed
                                your expectations.</p>

                            <p style="color: #212529; font-size: 16px; line-height: 1.6; margin: 20px 0 0 0;">Best
                                regards,<br><strong>{{ $company }} Team</strong></p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer-section"
                            style="background: #f8f9fa; border-top: 1px solid #dee2e6; color: #6c757d; text-align: center; padding: 25px 30px; font-size: 13px; line-height: 1.5;">
                            <p class="footer-text" style="margin: 0 0 5px 0;">This email was sent automatically by
                                {{ $company }} proposal system.</p>
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
