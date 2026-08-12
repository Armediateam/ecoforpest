<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateContract extends CreateRecord
{
    protected static string $resource = ContractResource::class;

    public function mount(): void
    {
        parent::mount();

        // Auto-fill form data based on URL parameters
        $this->autoFillFormData();
    }

    protected function autoFillFormData(): void
    {
        $request = request();
        
        if ($customerId = $request->get('customer_id')) {
            $customer = Customer::find($customerId);
            if ($customer) {
                $this->form->fill([
                    'customer_id' => $customer->id,
                    'subject' => "Contract for {$customer->name}" . ($customer->company ? " - {$customer->company}" : ''),
                    'is_proposal_order' => true,
                ]);
            }
        }
    }
}
