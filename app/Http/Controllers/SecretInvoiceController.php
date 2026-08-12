<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\InvoiceItem;
use App\Models\InvoiceService;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SecretInvoiceController extends Controller
{
    public function clone(Request $request)
    {
        $proposalId = $request->query('proposal_id');

        if (!$proposalId) {
            return back()->with('error', 'Proposal ID is required.');
        }

        $proposal = Proposal::with(['proposalOrder.proposalItems', 'proposalOrder.proposalServices', 'lead', 'customer'])->where('id', $proposalId)->first();

        if (!$proposal) {
            return back()->with('error', 'Proposal not found.');
        }

        DB::beginTransaction();
        try {
            // Determine Addresses
            $billingAddress = $proposal->address;
            $billingCity = $proposal->city;
            $billingState = $proposal->state;
            $billingZip = $proposal->zip_code;

            $countryName = null;
            if ($proposal->country_id) {
                $country = Country::find($proposal->country_id);
                $countryName = $country ? $country->name : null;
            }
            $billingCountry = $countryName;

            // Create Invoice
            $invoice = new Invoice();
            $invoice->related = $proposal->related;
            $invoice->lead_id = $proposal->lead_id;
            $invoice->customer_id = $proposal->customer_id;
            $invoice->status = 'draft';
            $invoice->invoice_date = now();
            // Default due date +7 days
            $invoice->invoice_due_date = now()->addDays(7);

            // Address mapping
            $invoice->billing_address = $billingAddress;
            $invoice->billing_city = $billingCity;
            $invoice->billing_state = $billingState;
            $invoice->billing_zip_code = $billingZip;
            $invoice->billing_country = $billingCountry;

            // Copy to shipping as well
            $invoice->shipping_address = $billingAddress;
            $invoice->shipping_city = $billingCity;
            $invoice->shipping_state = $billingState;
            $invoice->shipping_zip_code = $billingZip;
            $invoice->shipping_country = $billingCountry;

            $invoice->payment_term = $proposal->payment_term;
            $invoice->discount_type = $proposal->discount_type ?? 'before_tax'; // Default if null

            // Required fields with defaults
            $invoice->allowed_payment_method = ['bank_transfer']; // Default
            $invoice->recuring_invoices = 'no'; // Default

            // Totals from Proposal Order
            $order = $proposal->proposalOrder->first();
            if ($order) {
                $invoice->subtotal = $order->subtotal;
                $invoice->discount_fixed = $order->discount_fixed;
                $invoice->discount_percent = $order->discount_percent;
                $invoice->adjustment = $order->adjustment;
                $invoice->total = $order->total;
                $invoice->client_note = $order->client_note; // Maybe useful
                $invoice->terms_condition = $order->terms_condition;
            } else {
                $invoice->subtotal = 0;
                $invoice->total = 0;
            }

            $invoice->save(); // This triggers invoice_number generation in model boot()

            // Map Items
            if ($order && $order->proposalItems) {
                foreach ($order->proposalItems as $pItem) {
                    $item = new InvoiceItem();
                    $item->invoice_id = $invoice->id;
                    $item->item_id = $pItem->item_id;
                    $item->name = $pItem->name; // Map name
                    $item->description = $pItem->description;
                    $item->qty = $pItem->qty;
                    $item->qty_as = $pItem->qty_as; // Map qty_as
                    $item->unit = $pItem->unit; // Map unit
                    $item->rate = $pItem->rate;
                    $item->amount = $pItem->amount;
                    $item->save();

                    // attach taxes
                    if ($pItem->taxes) {
                        $item->taxes()->attach($pItem->taxes->pluck('id'));
                    }
                }
            }

            // Map Services
            if ($order && $order->proposalServices) {
                foreach ($order->proposalServices as $pService) {
                    $service = new InvoiceService();
                    $service->invoice_id = $invoice->id;
                    $service->service_id = $pService->service_id;
                    $service->name = $pService->name; // Map name if exists
                    $service->description = $pService->description;
                    $service->qty = $pService->qty;
                    $service->qty_as = $pService->qty_as; // Map qty_as
                    $service->unit = $pService->unit; // Map unit
                    $service->rate = $pService->rate;
                    $service->amount = $pService->amount;
                    $service->save();

                    // attach taxes
                    if ($pService->taxes) {
                        $service->taxes()->attach($pService->taxes->pluck('id'));
                    }
                }
            }

            DB::commit();

            return redirect("/secret/invoices/{$invoice->id}/edit");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Clone Proposal Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to convert proposal to invoice. ' . $e->getMessage()); // Simple error return, real world maybe Filament notification
        }
    }
}
