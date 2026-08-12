<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\EmployeeLocations;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Builder;

class LiveLocationTrackingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static string $view = 'filament.pages.live-location-tracking';
    protected static ?string $title = 'Live Location Tracking';
    protected static ?string $navigationLabel = 'Live Location Tracking';
    protected static ?string $navigationGroup = 'Human Resources';
    // protected static ?int $navigationSort = 10;

    // Add slug for routing
    protected static ?string $slug = 'live-location-tracking';

    public static function canAccess(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole('super_admin') ||
            auth()->user()->can('view_any_employee') ||
            auth()->user()->can('view_employee_location')
        );
    }

    public ?array $data = [];
    public $selectedDepartment = null;
    public $selectedPosition = null;
    public $selectedDate = null;

    public function mount(): void
    {
        $this->form->fill([
            'department_id' => null,
            'position_id' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('department_id')
                    ->label('Department')
                    ->placeholder('All Departments')
                    ->options(Department::pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn($state) => $this->selectedDepartment = $state),

                Select::make('position_id')
                    ->label('Position')
                    ->placeholder('All Positions')
                    ->options(function (callable $get) {
                        $departmentId = $get('department_id');
                        if ($departmentId) {
                            return Position::where('department_id', $departmentId)
                                ->pluck('title', 'id');
                        }
                        return Position::pluck('title', 'id');
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn($state) => $this->selectedPosition = $state),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function getLocationData()
    {
        // Get filter values dari request atau form state
        $departmentId = request('department_id') ?? $this->data['department_id'] ?? null;
        $positionId = request('position_id') ?? $this->data['position_id'] ?? null;

        // Subquery to get the latest location ID per employee for today
        $subQuery = EmployeeLocations::selectRaw('MAX(id) as id')
            ->whereDate('updated_at', now())
            ->groupBy('employee_id');

        $query = EmployeeLocations::with(['employee.position.department'])
            ->whereIn('id', $subQuery)
            ->whereHas('employee', function (Builder $builder) use ($departmentId, $positionId) {
                $builder->where('status', 'active');

                if ($departmentId) {
                    $builder->whereHas('position.department', function (Builder $q) use ($departmentId) {
                        $q->where('id', $departmentId);
                    });
                }

                if ($positionId) {
                    $builder->where('position_id', $positionId);
                }
            });

        return $query->latest('updated_at')->get()->map(function ($location) {
            return [
                'id' => $location->id,
                'employee_id' => $location->employee_id,
                'employee_name' => $location->employee->name,
                'employee_nik' => $location->employee->nik,
                'position' => $location->employee->position->title ?? 'N/A',
                'department' => $location->employee->position->department->name ?? 'N/A',
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'accuracy' => $location->accuracy ?? null,
                'info' => $location->info ? (is_string($location->info) ? $location->info : json_encode($location->info)) : null,
                'last_update' => $location->updated_at->toISOString(), // Use ISO format for better JavaScript parsing
                'last_update_formatted' => $location->updated_at->format('H:i:s'),
                'formatted_address' => $location->formatted_address,
                'photo_url' => $location->employee->photo_url,
                'status' => $location->employee->status,
            ];
        });
    }

    public function getMapboxKey(): string
    {
        return config('app.mapbox_key', env('MAPBOX_KEY', ''));
    }

    public function refreshLocations()
    {
        dd($this->getLocationData());
        // This method will be called via AJAX to get fresh location data
        return response()->json([
            'locations' => $this->getLocationData(),
            'timestamp' => now()->timestamp,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
