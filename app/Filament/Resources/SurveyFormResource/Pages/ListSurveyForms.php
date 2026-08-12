<?php

namespace App\Filament\Resources\SurveyFormResource\Pages;

use App\Filament\Resources\SurveyFormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Service;
use App\Models\SurveyForm;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;

class ListSurveyForms extends ListRecords
{
    protected static string $resource = SurveyFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('createMissingSurveyForms')
                ->label('Buat Survey Forms yang Hilang')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Buat Survey Forms yang Hilang')
                ->modalDescription('Ini akan membuat survey forms yang hilang untuk semua service yang belum lengkap. Apakah Anda yakin?')
                ->modalSubmitActionLabel('Ya, Buat Survey Forms')
                ->action(function () {
                    $this->createMissingSurveyForms();
                }),
            Actions\Action::make('showStatus')
                ->label('Lihat Status Survey Forms')
                ->icon('heroicon-o-information-circle')
                ->color('gray')
                ->action(function () {
                    $this->showSurveyStatus();
                }),
        ];
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery();
    }



    public function mount(): void
    {
        parent::mount();
        $this->checkIncompleteServices();
    }

    protected function checkIncompleteServices(): void
    {
        $services = Service::with('surveyForms')->get();
        $incompleteServices = [];

        foreach ($services as $service) {
            $existingTypes = $service->surveyForms->pluck('type')->toArray();
            $missingTypes = array_diff(SurveyForm::$types, $existingTypes);

            if (!empty($missingTypes)) {
                $incompleteServices[] = [
                    'service' => $service->name,
                    'missing_types' => array_map(function ($type) {
                        return match ($type) {
                            'identification' => 'Identifikasi',
                            'initial_check' => 'Pemeriksaan Awal',
                            'final_check' => 'Pemeriksaan Akhir',
                        };
                    }, $missingTypes)
                ];
            }
        }

        if (!empty($incompleteServices)) {
            $message = "Peringatan: Beberapa service belum memiliki survey form lengkap:\n\n";
            foreach ($incompleteServices as $incomplete) {
                $message .= "- {$incomplete['service']}: " . implode(', ', $incomplete['missing_types']) . "\n";
            }

            Notification::make()
                ->title('Survey Forms Tidak Lengkap')
                ->body($message)
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function createMissingSurveyForms(): void
    {
        $services = Service::with('surveyForms')->get();
        $createdCount = 0;

        foreach ($services as $service) {
            $existingTypes = $service->surveyForms->pluck('type')->toArray();
            $missingTypes = array_diff(SurveyForm::$types, $existingTypes);

            foreach ($missingTypes as $type) {
                SurveyForm::create([
                    'service_id' => $service->id,
                    'name' => $this->getDefaultSurveyName($type, $service->name),
                    'type' => $type,
                    'is_active' => true,
                    'fields' => $this->getDefaultFields($type),
                ]);
                $createdCount++;
            }
        }

        if ($createdCount > 0) {
            Notification::make()
                ->title('Berhasil!')
                ->body("{$createdCount} survey form berhasil dibuat untuk melengkapi semua service.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Tidak ada yang perlu dibuat')
                ->body('Semua service sudah memiliki survey forms lengkap.')
                ->info()
                ->send();
        }
    }

    protected function getDefaultSurveyName(string $type, string $serviceName): string
    {
        $typeLabels = [
            'identification' => 'Identifikasi',
            'initial_check' => 'Pemeriksaan Awal',
            'final_check' => 'Pemeriksaan Akhir',
        ];

        return "{$serviceName} - {$typeLabels[$type]}";
    }

    protected function getDefaultFields(string $type): array
    {
        // Return default fields based on type
        return [
            [
                'id' => 'notes',
                'label' => 'Catatan',
                'type' => 'textarea',
                'required' => false,
                'sort' => 1,
            ]
        ];
    }

    protected function showSurveyStatus(): void
    {
        $services = Service::with('surveyForms')->get();
        $completeServices = [];
        $incompleteServices = [];

        foreach ($services as $service) {
            $existingTypes = $service->surveyForms->pluck('type')->toArray();
            $missingTypes = array_diff(SurveyForm::$types, $existingTypes);
            $hasAllTypes = empty($missingTypes);

            if ($hasAllTypes) {
                $completeServices[] = $service->name;
            } else {
                $incompleteServices[] = [
                    'service' => $service->name,
                    'missing_types' => array_map(function ($type) {
                        return match ($type) {
                            'identification' => 'Identifikasi',
                            'initial_check' => 'Pemeriksaan Awal',
                            'final_check' => 'Pemeriksaan Akhir',
                        };
                    }, $missingTypes)
                ];
            }
        }

        $message = "Status Survey Forms per Service:\n\n";

        if (!empty($completeServices)) {
            $message .= "SERVICE LENGKAP:\n";
            foreach ($completeServices as $service) {
                $message .= "- {$service}\n";
            }
            $message .= "\n";
        }

        if (!empty($incompleteServices)) {
            $message .= "SERVICE TIDAK LENGKAP:\n";
            foreach ($incompleteServices as $incomplete) {
                $message .= "- {$incomplete['service']}: " . implode(', ', $incomplete['missing_types']) . "\n";
            }
        }

        if (empty($incompleteServices)) {
            $message .= "Selamat! Semua service sudah memiliki survey forms lengkap.";
        }

        Notification::make()
            ->title('Status Survey Forms')
            ->body($message)
            ->info()
            ->duration(10000)
            ->send();
    }
}
