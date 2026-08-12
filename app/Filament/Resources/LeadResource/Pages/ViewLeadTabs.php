<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Livewire\Components\Support\ResourceTabsConfiguration;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Lead;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use App\Models\Status as LeadStatus;
use App\Filament\Resources\CustomerResource;


class ViewLeadTabs extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected static string $view = 'filament.resources.lead-resource.pages.view-lead-tabs';

    public function getTabsConfiguration(): array
    {
        return ResourceTabsConfiguration::forLead()->toArray();
    }

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
                ->requiresConfirmation()
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
                ->hidden(fn(Lead $record): bool => Customer::where('lead_id', $record->id)->exists())
                ->visible(fn(): bool => auth()->user()->can('convert_lead')),

            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->color('warning')
                ->label('Edit Lead')
                ->visible(fn(): bool => auth()->user()->can('update_lead') && ! $this->record->trashed()),

            Actions\DeleteAction::make()
                ->visible(fn(): bool => auth()->user()->can('delete_lead') && ! $this->record->trashed()),

            Actions\ForceDeleteAction::make()
                ->visible(fn(): bool => auth()->user()->can('force_delete_lead') && $this->record->trashed()),

            Actions\RestoreAction::make()
                ->visible(fn(): bool => auth()->user()->can('restore_lead') && $this->record->trashed()),
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
            'google_maps_url'   => $record->google_maps_url,
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
            activity()
                ->performedOn($record)
                ->causedBy(auth()->user())
                ->withProperties([
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'status' => 'converted',
                    'converted_by' => auth()->user()->name,
                    'converted_at' => now()->toDateTimeString()
                ])
                ->log('converted');
            $record->save();
        } else {
            Notification::make()
                ->title('Lead Status Warning')
                ->body('The "Converted" lead status was not found. Please ensure it exists in your lead_statuses table.')
                ->warning()
                ->send(auth()->user());
        }
        $adminUsers = \App\Models\User::with('roles')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Admin Reguler');
            })->get();
        foreach ($adminUsers as $admin) {
            Notification::make()
                ->title('Lead Converted Successfully')
                ->body("Lead '{$record->name}' has been converted to customer '{$customer->name}'.")
                ->success()
                ->actions([
                    NotificationAction::make('view')
                        ->label('View Lead')
                        ->url(route('filament.secret.resources.leads.view', ['record' => $this->record->id])),
                ])
                ->sendToDatabase($admin);
        }

        if (class_exists(CustomerResource::class)) {
            return $this->redirect(CustomerResource::getUrl('view', ['record' => $customer->id]));
        } else {
            return $this->redirect(LeadResource::getUrl('index'));
        }
    }

    public function infolist(Infolist $infolist): Infolist
    {
        // Get the infolist from the resource class
        $resource = static::getResource();
        return $resource::infolist($infolist);
    }
}
