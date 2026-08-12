<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PDFDownloaderController extends Controller
{
    public function invoice(Request $request, $id)
    {
        $invoice = Invoice::where('id', $id)->firstOrFail();

        // Logic to generate and download PDF
        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice])
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'arial',
            ]);

        $safeInvoiceNumber = str_replace('/', '-', $invoice->invoice_number);

        return $pdf->download("ecoforpest-INV{$safeInvoiceNumber}.pdf");
    }
}
