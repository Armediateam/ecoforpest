<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Mail\PaymentMail;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Filament\Infolists\Components\Group;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sendEmail')
                ->disableLabel()
                ->color('gray')
                ->tooltip('Send Payment Receipt via Email')
                ->icon('heroicon-o-paper-airplane')
                ->form([
                    Forms\Components\TextInput::make('recipient_email')
                        ->label('Recipient Email')
                        ->email()
                        ->required()
                        ->default(fn($record) => $record->invoice->customer->email ?? '')
                        ->placeholder('Enter recipient email address'),
                    Forms\Components\Textarea::make('custom_message')
                        ->label('Custom Message')
                        ->placeholder('Add a personal message (optional)')
                        ->rows(4),
                ])
                ->action(function (array $data, $record) {
                    try {
                        $customMessage = $data['custom_message'] ?? '';
                        $recipientEmail = $data['recipient_email'];

                        // Create a temporary customer object for email if different recipient
                        if ($recipientEmail !== $record->invoice->customer->email) {
                            $tempCustomer = (object) [
                                'name' => 'Customer',
                                'email' => $recipientEmail
                            ];
                            $record->invoice->customer = $tempCustomer;
                        }

                        Mail::to($recipientEmail)->send(new PaymentMail($record, $customMessage));

                        Notification::make()
                            ->title('Email Sent Successfully')
                            ->body("Payment receipt has been sent to {$recipientEmail}")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Email Failed')
                            ->body('Failed to send email: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalHeading('Send Payment Receipt via Email')
                ->modalSubmitActionLabel('Send Email'),
            Actions\Action::make('downloadPdf')
                ->disableLabel()
                ->color('gray')
                ->tooltip('Download PDF Payment Receive')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function ($record) {
                    $pdf = Pdf::loadView('pdf.receipt', ['payment' => $record])
                        ->setPaper('letter', 'portrait')
                        ->setOptions([
                            'isHtml5ParserEnabled' => true,
                            'isRemoteEnabled' => true,
                            'defaultFont' => 'arial',
                        ]);
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->stream();
                    },  'Receipt - ecoforpest.pdf');
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Customer Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Group::make()
                                    ->schema([
                                        TextEntry::make('company.name')
                                            ->label('')
                                            ->default('CV. ECODWI BALI JAYA ABADI')
                                            ->weight('bold'),
                                        TextEntry::make('company.address')
                                            ->label('')
                                            ->default("Jl. Kebo Iwa Utara Gg.XV Blok C-4, Padangsambian Kaja, Denpasar Barat\nDenpasar Bali 80117"),
                                        TextEntry::make('company.npwp')
                                            ->label('')
                                            ->default('NPWP: 83.953.167.0-901.000'),
                                    ]),
                                Group::make()
                                    ->schema([
                                        TextEntry::make('invoice.customer.id')
                                            ->label('')
                                            ->weight('bold')
                                            ->alignEnd(),
                                        TextEntry::make('invoice.customer.name')
                                            ->label('')
                                            ->weight('bold')
                                            ->alignEnd(),
                                        TextEntry::make('invoice.customer.address')
                                            ->label('')
                                            ->alignEnd(),
                                        TextEntry::make('invoice.customer.zip_code')
                                            ->label('')
                                            ->lineClamp(2)
                                            ->alignEnd(),
                                    ]),
                            ])
                    ]),
                Section::make('Payment Receipt')
                    ->schema([
                        // Detail Pembayaran
                        TextEntry::make('payment_date')
                            ->label('Payment Date')
                            ->date('Y-m-d')
                            ->default('2025-06-27'),
                        TextEntry::make('payment_mode')
                            ->label('Payment Mode')
                            ->default('BCA (Ida Bagus Wiradnyana) - Transfer'),
                    ])->columns(2),
                Section::make()
                    ->schema([
                        TextEntry::make('amount')
                            ->label('Total Amount')
                            ->money('IDR')
                            ->size('lg')
                            ->weight('bold')
                            ->color('success')
                            ->default(750000),
                    ]),
                Section::make('Payment For')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('invoice_number_label')
                                    ->label('')
                                    ->default('Invoice Number')
                                    ->weight('bold'),
                                TextEntry::make('invoice_date_label')
                                    ->label('')
                                    ->default('Invoice Date')
                                    ->weight('bold'),
                                TextEntry::make('invoice_amount_label')
                                    ->label('')
                                    ->default('Invoice Amount')
                                    ->weight('bold'),
                                TextEntry::make('payment_amount_label')
                                    ->label('')
                                    ->default('Payment Amount')
                                    ->weight('bold'),

                                TextEntry::make('invoice.invoice_number')
                                    ->label('')
                                    ->default('ECOINV3421/06/2025'),
                                TextEntry::make('invoice.invoice_date')
                                    ->label('')
                                    ->date('Y-m-d')
                                    ->default('2025-06-28'),
                                TextEntry::make('invoice.total')
                                    ->label('')
                                    ->money('IDR')
                                    ->default(750000),
                                TextEntry::make('amount')
                                    ->label('')
                                    ->money('IDR')
                                    ->default(750000),
                            ]),
                    ]),
            ]);
    }
}
