<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProposalTemplatePreviewController;
use App\Http\Controllers\ProposalTemplatePreviewHtmlController;
use App\Http\Controllers\ProposalPreviewController;
use App\Http\Controllers\ContractTemplatePreviewController;
use App\Http\Controllers\ContractPreviewController;
use App\Filament\Pages\LiveLocationTrackingPage;

Route::get('/', function () {
    return redirect('/secret');
});

// Payment success and failure pages
Route::get('/payment/success', function () {
    return view('payment.success');
})->name('payment.success');

Route::get('/payment/failure', function () {
    return view('payment.failure');
})->name('payment.failure');


Route::middleware(['auth'])->group(function () {
    Route::get('proposal-templates/{template}/preview', ProposalTemplatePreviewController::class)
        ->name('proposal-templates.preview');

    Route::get('proposal-templates/{template}/preview-html', ProposalTemplatePreviewHtmlController::class)
        ->name('proposal-templates.preview-html');

    Route::get('proposals/{proposal}/preview', ProposalPreviewController::class)
        ->name('proposals.preview');

    Route::get('contract-templates/{contractTemplate}/preview', [ContractTemplatePreviewController::class, 'preview'])
        ->name('contract-templates.preview');

    Route::get('contract-templates/{contractTemplate}/preview-html', [ContractTemplatePreviewController::class, 'previewHtml'])
        ->name('contract-templates.preview-html');

    Route::get('contracts/{contract}/preview', ContractPreviewController::class)
        ->name('contracts.preview');

    Route::get('/invoices/create-clone', [\App\Http\Controllers\SecretInvoiceController::class, 'clone'])
        ->name('invoices.clone');

    Route::prefix('pdf')->group(function () {
        Route::get('invoice/{id}', [\App\Http\Controllers\PDFDownloaderController::class, 'invoice'])->name('pdf.invoice');
    });
});

Route::get('/service-report/{token}', [\App\Http\Controllers\API\ServiceReportController::class, 'publicShow']);
Route::post('/service-report/{token}/sign', [\App\Http\Controllers\API\ServiceReportController::class, 'publicSign']);
Route::post('/service-report/{token}/sign-technician', [\App\Http\Controllers\API\ServiceReportController::class, 'publicSignTechnician']);
Route::get('/service-report/{token}/preview', [\App\Http\Controllers\API\ServiceReportController::class, 'publicPdf']);

Route::get('/invoice/test/{id}', function ($id) {
    $invoice = \App\Models\Invoice::findOrFail($id);
    return view('pdf.invoice', [
        'invoice' => $invoice
    ]);
})->name('invoice.test');

// Test route for invoice email template
Route::get('/test-invoice-mail', function () {
    // Create a dummy invoice object for testing
    $invoice = (object) [
        'invoice_number' => 'ECOINV0001/08/2025',
        'invoice_date' => now()->format('Y-m-d'),
        'invoice_due_date' => now()->addDays(7)->format('Y-m-d'),
        'status' => 'sent',
        'total' => 2500000,
    ];

    // Create a dummy customer object
    $customer = (object) [
        'name' => 'John Doe',
        'email' => 'john.doe@example.com'
    ];

    // Get dynamic contact info
    $contactInfo = \App\Helpers\SettingsHelper::getEmailContactInfo();
    $company = $contactInfo['company_name'];
    $customMessage = 'Thank you for choosing our pest control services. We appreciate your business and look forward to continuing our partnership.';

    return view('emails.invoice', compact('invoice', 'customer', 'company', 'customMessage', 'contactInfo'));
})->name('test.invoice.mail');

// Test route for payment email template
Route::get('/test-payment-mail', function () {
    // Create a dummy payment object for testing
    $payment = (object) [
        'id' => 'PAY001',
        'amount' => 1500000,
        'payment_date' => now(),
        'payment_mode' => 'Bank Transfer',
        'notes' => 'Payment received via BCA transfer',
        'invoice' => (object) [
            'invoice_number' => 'ECOINV0001/08/2025',
            'total' => 2500000,
        ]
    ];

    // Create a dummy customer object
    $customer = (object) [
        'name' => 'John Doe',
        'email' => 'john.doe@example.com'
    ];

    // Get dynamic contact info
    $contactInfo = \App\Helpers\SettingsHelper::getEmailContactInfo();
    $company = $contactInfo['company_name'];
    $customMessage = 'Thank you for your prompt payment. We appreciate your continued trust in our services.';

    return view('emails.payment', compact('payment', 'customer', 'company', 'customMessage', 'contactInfo'));
})->name('test.payment.mail');

// Test route for proposal email template
Route::get('/test-proposal-mail', function () {
    // Create a dummy proposal object for testing
    $proposal = (object) [
        'id' => 'PROP001',
        'subject' => 'Comprehensive Pest Control Solution',
        'date' => now(),
        'status' => 'sent',
        'proposalOrder' => (object) [
            'total' => 5000000,
            'subtotal' => 5000000,
            'discount_fixed' => 0,
            'discount_percent' => 0,
            'adjustment' => 0,
        ]
    ];

    // Create a dummy customer object
    $customer = (object) [
        'name' => 'Jane Smith',
        'email' => 'jane.smith@example.com'
    ];

    // Get dynamic contact info
    $contactInfo = \App\Helpers\SettingsHelper::getEmailContactInfo();
    $company = $contactInfo['company_name'];
    $customMessage = 'We have carefully analyzed your requirements and prepared this comprehensive proposal tailored specifically for your business needs.';

    return view('emails.proposal', compact('proposal', 'customer', 'company', 'customMessage', 'contactInfo'));
})->name('test.proposal.mail');

// AJAX route for live location tracking
Route::post('/filament/live-locations', function () {
    $page = new LiveLocationTrackingPage();

    // Get parameters from request
    $departmentId = request('department_id');
    $positionId = request('position_id');
    $date = request('date');

    // Set parameters in page data
    if ($departmentId) $page->selectedDepartment = $departmentId;
    if ($positionId) $page->selectedPosition = $positionId;
    if ($date) $page->selectedDate = $date;

    return response()->json([
        'locations' => $page->getLocationData(),
        'timestamp' => now()->timestamp,
        'total_count' => $page->getLocationData()->count(),
    ]);
})->middleware(['auth', 'verified'])->name('filament.live-locations');


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
