<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Mail\InvoiceMail;
use App\Services\XenditPaymentService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\RawJs;
use Filament\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Infolists\Infolist;
use App\Livewire\Components\Support\ResourceTabsConfiguration;
use Filament\Forms;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.resources.invoice-resource.pages.view-invoice-tabs';

    protected function getHeaderActions(): array
    {
        $actions = [
            //
            Actions\Action::make('sendPaymentLinktoWhatsapp')
                ->disableLabel()
                ->color('gray')
                ->tooltip('Send Payment Link via WahtsApp')
                ->icon('heroicon-o-phone')

                ->visible(fn($record) => $record->payment_url)
                ->url(function ($record): ?string {
                    $phoneNumber = $record->customer->phone;

                    if (!$phoneNumber) {
                        Notification::make()
                            ->title('Gagal: Nomor Telepon Tidak Ditemukan')
                            ->danger()
                            ->send();
                        return null;
                    }

                    if (Str::startsWith($phoneNumber, '0')) {
                        $phoneNumber = '62' . substr($phoneNumber, 1);
                    }
                    $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
                    $paymentLink = $record->payment_url;
                    if (!$paymentLink || $record->isPaymentExpired()) {
                        // Asumsi Anda punya method untuk generate link di model atau service
                        $paymentLink = $record->generatePaymentLink();
                        $record->update(['payment_url' => $paymentLink]);
                    }
                    $message = "Halo {$record->customer->name},\n\n";
                    $message .= "Ini adalah link pembayaran untuk pesanan Anda #{$record->id}.\n";
                    $message .= "Silakan selesaikan pembayaran di sini: {$paymentLink}\n\n";
                    $message .= "Terima kasih!";

                    // Step 5: Encode pesan agar valid sebagai parameter URL
                    $encodedMessage = urlencode($message);

                    // Step 6: Buat URL wa.me
                    return "https://wa.me/{$phoneNumber}?text={$encodedMessage}";
                })
                ->openUrlInNewTab(),

            // Regenerate Payment URL action
            Actions\Action::make('regeneratePayment')
                ->label('Regenerate Payment URL')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn($record) => $record->isPaymentExpired())
                ->action(function ($record) {
                    try {
                        $record->createXenditPayment();

                        Notification::make()
                            ->title('Payment URL Regenerated')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to regenerate payment URL')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('sendEmail')
                ->disableLabel()
                ->color('gray')
                ->tooltip('Send Invoice via Email')
                ->icon('heroicon-o-paper-airplane')
                ->form([
                    Forms\Components\TextInput::make('recipient_email')
                        ->label('Recipient Email')
                        ->email()
                        ->required()
                        ->default(fn($record) => $record->customer->email ?? $record->lead->email ?? '')
                        ->placeholder('Enter recipient email address'),
                    Forms\Components\Textarea::make('custom_message')
                        ->label('Custom Message')
                        ->placeholder('Add a personal message (optional)')
                        ->rows(4)
                        ->default(fn($record) => $record->email_text ?? ''),
                ])
                ->action(function (array $data, $record) {
                    try {
                        $customMessage = $data['custom_message'] ?? '';
                        $recipientEmail = $data['recipient_email'];

                        // if (!$record->payment_url) {
                        //     $xenditService = app(XenditPaymentService::class);
                        //     $xenditService->createPayment($record);
                        // }

                        $recipientName = 'Customer';
                        if ($record->customer) {
                            $recipientName = $record->customer->name;
                        } elseif ($record->lead) {
                            $recipientName = $record->lead->name;
                        }

                        $tempCustomer = (object) [
                            'name' => $recipientName,
                            'email' => $recipientEmail
                        ];

                        $originalCustomer = $record->customer;
                        $originalLead = $record->lead;

                        $record->customer = $tempCustomer;

                        Mail::to($recipientEmail)->send(new InvoiceMail($record, $customMessage));

                        $record->customer = $originalCustomer;
                        $record->lead = $originalLead;

                        Notification::make()
                            ->title('Email Sent Successfully')
                            ->body("Invoice has been sent to {$recipientEmail}")
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
                ->modalHeading('Send Invoice via Email')
                ->modalSubmitActionLabel('Send Email'),
            Actions\Action::make('downloadPdf')
                ->hiddenLabel()
                ->color('gray')
                ->tooltip('Download PDF Invoice')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn($record) => route('pdf.invoice', ['id' => $record->id]))
                ->openUrlInNewTab(),
            Actions\EditAction::make()
                ->hiddenLabel()
                ->color('gray')
                ->tooltip('Edit Invoice')
                ->icon('heroicon-o-pencil-square'),
            Actions\Action::make('payment')
                ->label('Payment')
                ->color('success')
                ->icon('heroicon-o-document-currency-dollar')
                ->form([
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->label('Amount')
                        ->required()
                        ->prefix('Rp.')
                        ->mask(RawJs::make('$money($input, \',\')'))
                        ->stripCharacters('.')
                        ->numeric()
                        ->default($this->record->total),
                    \Filament\Forms\Components\DatePicker::make('payment_date')
                        ->label('Payment Date')
                        ->default(now())
                        ->required(),
                    \Filament\Forms\Components\Select::make('payment_mode')
                        ->label('Payment Mode')
                        ->options(function () {
                            $banksSetting = \App\Models\Setting::where('key', 'banks')->first();

                            if ($banksSetting && $banksSetting->value) {
                                $banksData = is_array($banksSetting->value) ? $banksSetting->value : json_decode($banksSetting->value, true);

                                if (is_array($banksData)) {
                                    // Use bank name as both key and value for selection options
                                    $options = [];
                                    foreach ($banksData as $key => $value) {
                                        $options[$key] = $key;
                                    }
                                    return $options;
                                }
                            }

                            // Fallback to default values if setting not found
                            return [
                                'Tunai' => 'Tunai'
                            ];
                        })
                        ->required(),
                    \Filament\Forms\Components\FileUpload::make('receipt')
                        ->label('Receipt')
                        ->maxSize(2048) // 2MB
                        ->helperText('Upload a receipt image or PDF.'),
                    \Filament\Forms\Components\TextInput::make('transaction_id')
                        ->label('Transaction ID')
                        ->maxLength(255),
                    \Filament\Forms\Components\Textarea::make('note')
                        ->label('Note')
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    $data['amount'] = (float) str_replace([','], '', $data['amount']);
                    $payment = $this->record->payment()->create($data);
                    $totalPayments = (float) $this->record->payment()->sum('amount');
                    $invoiceTotal = (float) $this->record->total;
                    if ($totalPayments >= $invoiceTotal) {
                        $this->record->update(['status' => 'Paid']);
                    } elseif ($totalPayments > 0 && $totalPayments < $invoiceTotal) {
                        $this->record->update(['status' => 'DOWN PAYMENT']);
                    }

                    Notification::make()
                        ->title('Payment recorded successfully')
                        ->success()
                        ->send(auth()->user());

                    return redirect()->route('filament.secret.resources.payments.view', [
                        'record' => $this->record->payment()->latest()->first(),
                    ]);
                })
        ];

        return $actions;
    }

    public function getTabsConfiguration(): array
    {
        return ResourceTabsConfiguration::forInvoice()->toArray();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        // Get the infolist from the resource class
        $resource = static::getResource();
        return $resource::infolist($infolist);
    }
}
