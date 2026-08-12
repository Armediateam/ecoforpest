<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Models\Proposal;
use App\Models\Setting;
use App\Traits\GridBuilderConverter;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\PhoneHelper;

class ProposalPreviewController extends Controller
{
    use GridBuilderConverter;
    public function __invoke(Proposal $proposal)
    {
        $proposal->load(['proposalTemplate', 'country', 'paymentTerm', 'customer', 'lead', 'proposalOrder']);

        if ($proposal->proposalOrder) {
            try {
                $proposal->load('proposalOrder.proposalItems');
                if ($proposal->proposalOrder && !$proposal->proposalOrder->proposalItems) {
                    $proposal->proposalOrder->proposalItems = collect();
                }
            } catch (\Exception $e) {
                $proposal->proposalOrder->proposalItems = collect();
            }

            try {
                $proposal->load('proposalOrder.proposalServices');
                if ($proposal->proposalOrder && !$proposal->proposalOrder->proposalServices) {
                    $proposal->proposalOrder->proposalServices = collect();
                }
            } catch (\Exception $e) {
                $proposal->proposalOrder->proposalServices = collect();
            }
        }

        $customer = ($proposal->related == 'customer') ? $proposal->customer : $proposal->lead;
        $order = $proposal->proposalOrder ? $proposal->proposalOrder->first() : null;

        // Process template content with dynamic placeholders
        $templateContent = $this->processTemplateContent($proposal, $customer, $order);

        $data = [
            'proposal' => $proposal,
            'customer' => $customer,
            'order' => $order,
            'company' => config('app.name'),
            'date' => Carbon::parse($proposal->date)->format('d F Y'),
            'content' => $templateContent,
            'hasTemplate' => !empty($proposal->proposalTemplate?->content),
        ];

        // Choose the appropriate view based on whether template exists
        $view = $data['hasTemplate'] ? 'proposals.template-preview' : 'proposals.preview';

        $pdf_load = PDF::loadView($view, $data)
            ->setPaper('a4');
            // ->stream("proposal-{$proposal->id}.pdf");
        return response()->streamDownload(function () use ($pdf_load) {
                echo $pdf_load->stream();
        },  "proposal-{$proposal->id}.pdf");
        // debug blade view
        // return view($view, $data);
    }

    /**
     * Process template content and replace placeholders with actual data
     */
    private function processTemplateContent($proposal, $customer, $order = null)
    {
        $content = $proposal->proposalTemplate?->content ?? '';

        if (empty($content)) {
            return '';
        }

        // First convert tiptap JSON to HTML
        $htmlContent = tiptap_converter()->asHTML($content);

        // Calculate totals
        $subtotal = $order?->subtotal ?? 0;
        $discountFixed = $order?->discount_fixed ?? 0;
        $discountPercent = $order?->discount_percent ?? 0;
        $adjustment = $order?->adjustment ?? 0;
        $total = $order?->total ?? 0;

        $companyInfo = SettingsHelper::getContactInformation();

        // Prepare replacement data
        $replacements = [
            '{customer}' => $customer?->name ?? 'N/A',
            '{customer_name}' => $customer?->name ?? 'N/A',
            '{customer_email}' => $customer?->email ?? $proposal->email ?? 'N/A',
            '{customer_phone}' => PhoneHelper::clean($customer?->phone) ?? PhoneHelper::clean($proposal->phone) ?? 'N/A',
            '{customer_address}' => $customer?->address ?? $proposal->address ?? 'N/A',
            '{customer_city}' => $customer?->city ?? $proposal->city ?? 'N/A',
            '{customer_state}' => $customer?->state ?? $proposal->state ?? 'N/A',
            '{customer_zip_code}' => $customer?->zip_code ?? $proposal->zip_code ?? 'N/A',
            '{customer_country}' => $customer?->country?->name ?? $proposal->country?->name ?? 'N/A',
            '{company_name}' => $companyInfo['company_name'] ?? 'N/A',
            '{company_address}' => $companyInfo['address'] ?? 'N/A',
            '{company_email}' => $companyInfo['email'] ?? 'N/A',
            '{company_phone}' => $companyInfo['phone'] ?? 'N/A',
            '{company_website}' => $companyInfo['website'] ?? 'N/A',
            '{company_npwp}' => $companyInfo['npwp'] ?? 'N/A',
            '{date}' => Carbon::parse($proposal->date)->format('d F Y'),
            '{proposal_date}' => Carbon::parse($proposal->date)->format('d F Y'),
            '{valid_until}' => $proposal->open_till ? Carbon::parse($proposal->open_till)->format('d F Y') : 'N/A',
            '{proposal_number}' => $proposal->id ?? 'Draft',
            '{proposal_subject}' => $proposal->subject ?? 'N/A',
            '{subtotal}' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
            '{discount_fixed}' => 'Rp ' . number_format($discountFixed, 0, ',', '.'),
            '{discount_percent}' => $discountPercent . '%',
            '{adjustment}' => 'Rp ' . number_format($adjustment, 0, ',', '.'),
            '{total}' => 'Rp ' . number_format($total, 0, ',', '.'),
            '{payment_term}' => $proposal->paymentTerm?->name ?? 'N/A',
            '{contract_start}' => $proposal->contract_start_date ? Carbon::parse($proposal->contract_start_date)->format('d F Y') : 'N/A',
            '{contract_end}' => $proposal->contract_end_date ? Carbon::parse($proposal->contract_end_date)->format('d F Y') : 'N/A',
            '{warranty_term}' => $proposal->warranty_term ?? 'N/A',
            '{warranty_type}' => $proposal->warranty_type ?? 'N/A',
            '{to}' => $proposal->to ?? 'N/A',
            '{items_table}' => $this->generateTableHtml($order ? ($order->proposalItems ?? collect()) : collect(), 'items'),
            '{services_table}' => $this->generateTableHtml($order ? ($order->proposalServices ?? collect()) : collect(), 'services'),
            '{pricing_details}' => $this->generatePricingDetailsHtml($order),
            '{service_details}' => $this->generateServiceDetailsHtml($order),
        ];

        // Replace placeholders in HTML content
        $processedContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $htmlContent
        );

        // Replace all image src from relative to base64 urls
        $processedContent = preg_replace_callback('/<img[^>]+src="([^">]+)"/', function ($matches) {
            $src = $matches[1];
            $imageData = @file_get_contents($src);
            if ($imageData) {
                $base64 = base64_encode($imageData);
                $mimeType = mime_content_type($src);
                return '<img src="data:' . $mimeType . ';base64,' . $base64 . '"';
            } else {
                // Use placeholder logo if image is not available
                $placeholderPath = public_path('ecoforpest.png');
                $placeholderData = @file_get_contents($placeholderPath);
                if ($placeholderData) {
                    $base64 = base64_encode($placeholderData);
                    $mimeType = mime_content_type($placeholderPath);
                    return '<img src="data:' . $mimeType . ';base64,' . $base64 . '"';
                }
                return $matches[0];
            }
        }, $processedContent);

        // Convert Filament TipTap grid builder to PDF-compatible table layout
        $processedContent = $this->convertGridBuilderToTable($processedContent);

        return $processedContent;
    }

    /**
     * Generate HTML for items or services table
     */
    private function generateTableHtml($collection, $type)
    {
        if (!$collection || $collection->count() == 0) {
            return '';
        }

        $html = '<div class="items-section">';
        $html .= '<table class="table">';
        $html .= '<thead>';
        $html .= '<tr>';

        if ($type === 'items') {
            $html .= '<th>Item</th>';
            $html .= '<th>Qty</th>';
            $html .= '<th>Rate</th>';
            $html .= '<th>Tax</th>';
            $html .= '<th>Amount</th>';
        } else { // services
            $html .= '<th>Service</th>';
            $html .= '<th>Qty</th>';
            $html .= '<th>Rate</th>';
            $html .= '<th>Tax</th>';
            $html .= '<th>Amount</th>';
        }

        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($collection as $item) {
            $html .= '<tr>';
            $html .= '<td>';
            $html .= '<div><strong>' . ($item->name ?? '-') . '</strong></div>';
            $html .= '<div>' . nl2br($item->description ?? '-') . '</div>';
            $html .= '</td>';

            if ($type === 'items') {
                $html .= '<td>' . ($item->qty ?? '-') . '</td>';
                $html .= '<td>Rp ' . (isset($item->rate) ? number_format($item->rate, 0, ',', '.') : '-') . '</td>';

                if ($item->taxes() && $item->taxes()->count() > 0) {
                    $html .= '<td>';
                    foreach ($item->taxes as $tax) {
                        $html .= '<div>' . $tax->name . ' (' . number_format(($item->amount * $tax->value) / 100, 0, ',', '.') . ')</div>';
                    }
                    $html .= '</td>';
                } else {
                    $html .= '<td>-</td>';
                }

                $html .= '<td>Rp ' . (isset($item->amount) ? number_format($item->amount, 0, ',', '.') : '-') . '</td>';
            } else { // services
                $html .= '<td>' . ($item->qty ?? '-') . ' ' . ($item->unit ?? '') . '</td>';
                $html .= '<td>Rp ' . (isset($item->rate) ? number_format($item->rate, 0, ',', '.') : '-') . '</td>';

                if ($item->taxes() && $item->taxes()->count() > 0) {
                    $html .= '<td>';
                    foreach ($item->taxes as $tax) {
                        $html .= '<div>' . $tax->name . ' (' . number_format(($item->amount * $tax->value) / 100, 0, ',', '.') . ')</div>';
                    }
                    $html .= '</td>';
                } else {
                    $html .= '<td>-</td>';
                }

                $html .= '<td>Rp ' . (isset($item->amount) ? number_format($item->amount, 0, ',', '.') : '-') . '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate HTML for pricing details (subtotal, discounts, adjustment, total)
     */
    private function generatePricingDetailsHtml($order)
    {
        if (!$order) {
            return '';
        }

        $html = '<div class="total-section">';
        $html .= '<table class="total-table">';
        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td class="label">Subtotal:</td>';
        $html .= '<td class="value">Rp ' . number_format($order->subtotal ?? 0, 0, ',', '.') . '</td>';
        $html .= '</tr>';

        if (($order->discount_fixed ?? 0) > 0) {
            $html .= '<tr>';
            $html .= '<td class="label">Discount:</td>';
            $html .= '<td class="value">Rp ' . number_format($order->discount_fixed, 0, ',', '.') . '</td>';
            $html .= '</tr>';
        }

        if (($order->discount_percent ?? 0) > 0) {
            $html .= '<tr>';
            $html .= '<td class="label">Discount:</td>';
            $html .= '<td class="value">' . $order->discount_percent . '%</td>';
            $html .= '</tr>';
        }

        if ($order->adjustment) {
            $html .= '<tr>';
            $html .= '<td class="label">Adjustment:</td>';
            $html .= '<td class="value">Rp ' . number_format($order->adjustment, 0, ',', '.') . '</td>';
            $html .= '</tr>';
        }

        $html .= '<tr class="total-line-total">';
        $html .= '<td class="label">Total:</td>';
        $html .= '<td class="value">Rp ' . number_format($order->total ?? 0, 0, ',', '.') . '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate HTML for service details (target, treatment area, method, unit/amount)
     */
    private function generateServiceDetailsHtml($order)
    {
        if (!$order || !isset($order->target_detail) || !is_array($order->target_detail) || count($order->target_detail) == 0) {
            return '';
        }

        $html = '<div class="service-details">';
        $html .= '<h3>Service Details</h3>';
        $html .= '<table class="table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Target</th>';
        $html .= '<th>Treatment Area</th>';
        $html .= '<th>Method</th>';
        $html .= '<th>Unit/Amount</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($order->target_detail as $target) {
            $html .= '<tr>';
            $html .= '<td>' . ($target['target'] ?? '-') . '</td>';
            $html .= '<td>' . ($target['treatment_area'] ?? '-') . '</td>';
            $html .= '<td>' . ($target['method_use'] ?? '-') . '</td>';
            $html .= '<td>' . ($target['unit_amount'] ?? '-') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }
}
