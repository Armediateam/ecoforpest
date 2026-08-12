<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Filament\Resources\CustomerResource;
use App\Models\Lead;
use App\Models\Status as LeadStatus;
use App\Models\Customer;
use App\Models\CustomerGroup;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_customer')
                ->label('View Customer Detail')
                ->color('info')
                ->icon('heroicon-o-user')
                ->action(function (Lead $record) {
                    $customer = Customer::where('lead_id', $record->id)->first();
                    if ($customer && class_exists(CustomerResource::class)) {
                        return $this->redirect(CustomerResource::getUrl('view', ['record' => $customer->id]));
                    }
                })
                ->hidden(fn(Lead $record): bool => !Customer::where('lead_id', $record->id)->exists()),

            Action::make('convert')
                ->label('Convert to Customer')
                ->color('success')
                ->icon('heroicon-o-arrow-path')
                ->modalHeading('Convert Lead to Customer')
                ->modalSubmitActionLabel('Confirm Conversion')
                ->form([
                    Forms\Components\Select::make('customer_group_id')
                        ->label('Customer Group')
                        ->options(CustomerGroup::query()->pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('Select a customer group (Optional)'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Set customer as active?')
                        ->default(true),
                ])
                ->action(fn(array $data, Lead $record) => $this->performConversion($data, $record))
                ->hidden(fn(Lead $record): bool => Customer::where('lead_id', $record->id)->exists()),
        ];
    }
    protected function performConversion(array $data, Lead $record)
    {
        if (Customer::where('lead_id', $record->id)->exists()) {
            Notification::make()
                ->title('Already Converted')
                ->body('This lead has already been converted to a customer.')
                ->warning()
                ->send();
            return;
        }
        $customer = Customer::create([
            'lead_id'           => $record->id,
            'name'              => $record->name,
            'address'           => $record->address,
            'position'          => $record->position,
            'email'             => $record->email,
            'city'              => $record->city,
            'state'             => $record->state,
            'country_id'        => $record->country_id,
            'zip_code'          => $record->zip_code,
            'default_language'  => $record->default_language,
            'website'           => $record->website,
            'company'           => $record->company,
            'description'       => $record->description,
            'phone'             => $record->phone,
            'customer_group_id' => $data['customer_group_id'] ?? null,
            'is_active'         => $data['is_active'] ?? true,
        ]);
        $convertedStatus = LeadStatus::where('name', 'Converted')->first();
        if ($convertedStatus) {
            $record->status_id = $convertedStatus->id;
            $record->save();
        } else {
            Notification::make()
                ->title('Lead Status Warning')
                ->body('The "Converted" lead status was not found. Please ensure it exists in your lead_statuses table.')
                ->warning()
                ->send(auth()->user());
        }
        Notification::make()
            ->title('Lead Converted Successfully')
            ->body("Lead '{$record->name}' has been converted to customer '{$customer->name}'.")
            ->success()
            ->send();
        if (class_exists(CustomerResource::class)) {
            return $this->redirect(CustomerResource::getUrl('view', ['record' => $customer->id]));
        } else {
            return $this->redirect(LeadResource::getUrl('index'));
        }
    }
}
