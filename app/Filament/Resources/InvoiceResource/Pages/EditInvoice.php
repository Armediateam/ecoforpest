<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Invoice;
use Filament\Notifications\Notification;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public ?string $originalStatus = null;
    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->originalStatus = $this->record->status;
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    // mutate invoice number before fill
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['invoice_number']) && $data['invoice_number'] === 'ECOINVDRAFT') {
            $data['invoice_number'] = Invoice::generateInvoiceNumber();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (
            ($this->originalStatus === 'Draft' || $this->originalStatus === 'Unpaid')  &&
            $this->record->status === 'Unpaid' &&
            $this->record->payment_type === 'xendit'
        ) {
            try {
                // Panggil method untuk membuat pembayaran Xendit
                $this->generatePaymentUrl();
            } catch (\Exception $e) {
                // Log error tapi jangan block proses
                \Log::error('Xendit payment creation failed on status change: ' . $e->getMessage(), [
                    'invoice_id' => $this->record->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    protected function generatePaymentUrl(): void
    {
        try {
            $this->record->createXenditPayment();
            
            Notification::make()
                ->title('Payment URL Generated')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to generate payment URL')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
