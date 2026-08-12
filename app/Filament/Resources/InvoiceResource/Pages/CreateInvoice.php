<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Customer;
use App\Models\Lead;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public function mount(): void
    {
        parent::mount();

        // Auto-fill form data based on URL parameters
        $this->autoFillFormData();
    }

    protected function autoFillFormData(): void
    {
        $request = request();
        $autoFillData = [];

        if ($leadId = $request->get('lead_id')) {
            $lead = Lead::find($leadId);
            if ($lead) {
                $autoFillData = $this->getLeadData($lead);
            }
        } elseif ($customerId = $request->get('customer_id')) {
            $customer = Customer::find($customerId);
            if ($customer) {
                $autoFillData = $this->getCustomerData($customer);
            }
        }

        if (!empty($autoFillData)) {
            $this->form->fill($autoFillData);
        }
    }

    protected function getCustomerData(Customer $customer): array
    {
        return [
            'created_by' => auth()->user()->id,
            'related' => 'customer',
            'customer_id' => $customer->id,
            'billing_address' => $customer->address,
            'billing_city' => $customer->city,
            'billing_state' => $customer->state,
            'billing_zip_code' => $customer->zip_code,
            'billing_country' => $customer->country->name ?? null,
            'payment_type' => 'manual',
            'allowed_payment_method' => $this->getDefaultPaymentMethod(),
        ];
    }

    protected function getLeadData(Lead $lead): array
    {
        return [
            'created_by' => auth()->user()->id,
            'related' => 'lead',
            'lead_id' => $lead->id,
            'to' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'billing_address' => $lead->address,
            'billing_city' => $lead->city,
            'billing_state' => $lead->state,
            'billing_zip_code' => $lead->zip_code,
            'billing_country' => $lead->country->name ?? null,
            'payment_type' => 'manual',
            'allowed_payment_method' => $this->getDefaultPaymentMethod(),
        ];
    }

    // adding function get payment method from settings
    protected function getDefaultPaymentMethod(): array | string
    {
        $banksSetting = \App\Models\Setting::where('key', 'banks')->first();
        if ($banksSetting && $banksSetting->value) {
            $banksData = is_array($banksSetting->value) ? $banksSetting->value : json_decode($banksSetting->value, true);
            if (is_array($banksData)) {
                return array_keys($banksData);
            }
        }
        return ['Tunai'];
    }

    // adding action create draft invoice
    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
        ];
    }

    protected function afterCreate(): void
    {
        // Create Xendit payment URL after invoice creation
        if ($this->record->status !== 'Draft' && $this->record->payment_type == 'xendit') {
            $this->generatePaymentUrl();
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
