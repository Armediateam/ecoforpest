<?php

namespace App\Filament\Resources\ProposalResource\Pages;

use App\Filament\Resources\ProposalResource;
use App\Models\Lead;
use App\Models\Customer;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CreateProposal extends CreateRecord
{
    protected static string $resource = ProposalResource::class;

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
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
            'address' => $lead->address,
            'city' => $lead->city,
            'state' => $lead->state,
            'zip_code' => $lead->zip_code,
            'country_id' => $lead->country_id,
            'subject' => "Proposal for {$lead->name}" . ($lead->company ? " - {$lead->company}" : ''),
        ];
    }

    protected function getCustomerData(Customer $customer): array
    {
        return [
            'created_by' => auth()->user()->id,
            'related' => 'customer',
            'customer_id' => $customer->id,
            'to' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'city' => $customer->city,
            'state' => $customer->state,
            'zip_code' => $customer->zip_code,
            'country_id' => $customer->country_id,
            'subject' => "Proposal for {$customer->name}" . ($customer->company ? " - {$customer->company}" : ''),
        ];
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Created')
            ->success()
            ->send();

        $proposal = $this->record;
        if ($proposal->assigned) {
            Notification::make()
                ->title('Proposals are assigned')
                ->body('This proposal is assigned to you')
                ->success()
                ->actions([
                    Action::make('view')
                        ->label('View Proposal')
                        ->url(route('filament.secret.resources.proposals.view', ['record' => $proposal->id])),
                ])
                ->sendToDatabase($proposal->assigned);
        }
    }
}
