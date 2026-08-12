<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Filament\Resources\WorkOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\WorkOrder;
use App\Models\Setting;
use App\Models\Customer;
use App\Models\Lead;
use Filament\Support\Exceptions\Halt;
use Carbon\Carbon;

class CreateWorkOrder extends CreateRecord
{
    protected static string $resource = WorkOrderResource::class;

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
            'related' => 'customer',
            'customer_id' => $customer->id,
            'alamat' => $customer->address . ' ' . ($customer->city ?? '') . ' ' . ($customer->state ?? '') . ' ' . ($customer->zip_code ?? '') . ' ' . ($customer->country->name ?? ''),
        ];
    }

    protected function getLeadData(Lead $lead): array
    {
        return [
            'related' => 'lead',
            'lead_id' => $lead->id,
            'alamat' => $lead->address . ' ' . ($lead->city ?? '') . ' ' . ($lead->state ?? '') . ' ' . ($lead->zip_code ?? '') . ' ' . ($lead->country->name ?? null),
        ];
    }

    protected function afterCreate(): void
    {
        if (function_exists('activity')) {
            activity()
                ->performedOn($this->record)
                ->causedBy(auth()->user())
                ->log('created');
        }
        $data = $this->data;
        if (!empty($data['is_recuring'])) {
            $repeatEvery = (int) ($data['repeat_every'] ?? 1);
            $repeatType = $data['repeat_type'] ?? 'week';
            $repeatCycle = (int) ($data['repeat_cycle'] ?? 1);
            $baseDate = Carbon::parse($data['work_date']);
            // Hitung jumlah work order customer sebelum recurring
            $customerId = $data['customer_id'] ?? null;
            $existingCount = $customerId ? \App\Models\WorkOrder::where('customer_id', $customerId)->count() : 0;
            // Ambil template mentah dari ScopeOfWorkTemplate jika ada
            // Jika tidak ada scope_of_work_template_id, ambil template mentah dari detail_work
            $rawTemplate = null;
            if (!empty($data['scope_of_work_template_id'])) {
                $templateModel = \App\Models\ScopeOfWorkTemplate::find($data['scope_of_work_template_id']);
                if ($templateModel) {
                    $rawTemplate = $templateModel->content;
                }
            } else if (!empty($data['detail_work'])) {
                // $rawTemplate = $data['detail_work'];
                $rawTemplate = tiptap_converter()->asHTML($data['detail_work']);
            }
            $recurringDates = $data['recurring_dates'] ?? [];

            foreach ($recurringDates as $recurringDate) {
                $newData = $data;
                // if (isset($newData['total'])) {
                //     if (is_numeric($newData['total'])) {
                //         $newData['total'] = (double) $newData['total'];
                //     } else {
                //         $total = str_replace(['Rp', ' '], '', $newData['total']); // Remove currency and spaces
                //         $total = str_replace('.', '', $total); // Remove thousand separators
                //         $total = str_replace(',', '.', $total); // Convert decimal separator
                //         $newData['total'] = (double) $total; // Cast to double for database
                //     }
                // }
                // $newData['total'] = (float) preg_replace('/[^0-9.]/', '', $newData['total']);
                $newData['work_date'] = $recurringDate['date'];
                $newData['work_time'] = $recurringDate['time'];
                $newData['is_recuring'] = false; // Hanya work order pertama yang recurring
                unset($newData['repeat_every'], $newData['repeat_type'], $newData['repeat_cycle'], $newData['recurring_dates']);
                $newData['position'] = $this->record->position;

                // Render ulang template detail_work agar variabel sesuai data baru
                $template = $rawTemplate ?? $this->record->detail_work;
                if (method_exists(WorkOrderResource::class, 'renderScopeOfWorkTemplate')) {
                    $newData['detail_work'] = WorkOrderResource::renderScopeOfWorkTemplate($template, fn($key) => $newData[$key] ?? null);
                } else {
                    $newData['detail_work'] = $template;
                }

                \App\Models\WorkOrder::create($newData);
            }
        }
    }

    protected function beforeCreate(): void
    {
        $maxWorkOrder = (int) Setting::where('key', 'max_work_order')->first()?->value;
        if ($maxWorkOrder <= 0) {
            return;
        }

        $workDate = $this->data['work_date'];

        $currentWorkOrdersCount = static::getModel()::whereDate('work_date', $workDate)->count();

        if ($currentWorkOrdersCount >= $maxWorkOrder) {
            Notification::make()
                ->title('Gagal Membuat Work Order')
                ->body("Batas maksimal work order untuk tanggal tersebut ({$maxWorkOrder} record) telah tercapai.")
                ->color('danger')
                ->duration(5000)
                ->danger()
                ->send();

            throw new Halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Clean total field by removing non-numeric characters except decimal point
        if (isset($data['total'])) {
            // $data['total'] = (float) preg_replace('/[^0-9.]/', '', $data['total']);
            $data['total'] = (float) preg_replace('/[^0-9]/', '', $data['total']);
        }

        // Clean price fields in workOrderPackage repeater
        if (isset($data['workOrderPackage']) && is_array($data['workOrderPackage'])) {
            foreach ($data['workOrderPackage'] as &$package) {
                if (isset($package['price'])) {
                    // $package['price'] = (float) preg_replace('/[^0-9.]/', '', $package['price']);
                    $package['price'] = (float) preg_replace('/[^0-9]/', '', $package['price']);
                }
            }
        }

        return $data;
    }

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     return $this->mutateFormDataBeforeSave($data);
    // }
}
