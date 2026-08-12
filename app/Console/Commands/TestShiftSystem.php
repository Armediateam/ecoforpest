<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\Shift;

class TestShiftSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shift:test {--demo : Run demo data setup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the new hybrid shift system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Testing Hybrid Shift System');
        $this->newLine();

        if ($this->option('demo')) {
            $this->setupDemoData();
        }

        $this->testShiftHierarchy();
        $this->newLine();
        $this->displayShiftDistribution();
    }

    private function setupDemoData()
    {
        $this->info('📊 Setting up demo data...');

        // Get or create shifts
        $regularShift = Shift::where('name', 'Regular Shift')->first();
        $morningShift = Shift::where('name', 'Morning Shift')->first();
        $nightShift = Shift::where('name', 'Night Shift')->first();

        if (!$regularShift || !$morningShift || !$nightShift) {
            $this->error('❌ Shifts not found. Please run: php artisan db:seed --class=ShiftSeeder');
            return;
        }

        // Set department default shifts
        $departments = Department::take(3)->get();
        foreach ($departments as $index => $department) {
            $shifts = [$regularShift, $morningShift, $nightShift];
            if (isset($shifts[$index])) {
                $department->default_shift_id = $shifts[$index]->id;
                $department->save();
                $this->line("  ✅ Set {$shifts[$index]->name} as default for department: {$department->name}");
            }
        }

        // Set position default shifts for some positions
        $positions = Position::take(2)->get();
        foreach ($positions as $index => $position) {
            if ($index === 0 && $nightShift) {
                $position->default_shift_id = $nightShift->id;
                $position->save();
                $this->line("  ✅ Set {$nightShift->name} as default for position: {$position->name}");
            }
        }

        // Set individual employee shifts for some employees
        $employees = Employee::take(3)->get();
        foreach ($employees as $index => $employee) {
            if ($index === 0 && $morningShift) {
                $employee->shift_id = $morningShift->id;
                $employee->save();
                $this->line("  ✅ Set {$morningShift->name} override for employee: {$employee->name}");
            }
        }

        $this->newLine();
    }

    private function testShiftHierarchy()
    {
        $this->info('🔍 Testing Shift Hierarchy (First 10 employees):');

        $employees = Employee::with(['shift', 'position.defaultShift', 'position.department.defaultShift'])
            ->take(10)
            ->get();

        if ($employees->isEmpty()) {
            $this->warn('⚠️  No employees found in database.');
            return;
        }

        $tableData = [];
        foreach ($employees as $employee) {
            $effectiveShift = $employee->getEffectiveShift();

            $shiftSource = 'None';
            if ($employee->shift_id) {
                $shiftSource = 'Employee Override';
            } elseif ($employee->position && $employee->position->default_shift_id) {
                $shiftSource = 'Position Default';
            } elseif ($employee->position && $employee->position->department && $employee->position->department->default_shift_id) {
                $shiftSource = 'Department Default';
            }

            $tableData[] = [
                substr($employee->name, 0, 20),
                substr($employee->position?->name ?? 'N/A', 0, 15),
                substr($employee->position?->department?->name ?? 'N/A', 0, 15),
                $effectiveShift?->name ?? 'No Shift',
                $shiftSource
            ];
        }

        $this->table(
            ['Employee', 'Position', 'Department', 'Effective Shift', 'Source'],
            $tableData
        );
    }

    private function displayShiftDistribution()
    {
        $this->info('📊 Shift Distribution:');

        $shifts = Shift::all();
        $totalEmployees = Employee::count();

        if ($totalEmployees === 0) {
            $this->warn('⚠️  No employees found in database.');
            return;
        }

        $tableData = [];
        foreach ($shifts as $shift) {
            $count = 0;

            // Count employees with direct shift assignment
            $count += Employee::where('shift_id', $shift->id)->count();

            // Count employees using this shift through position default
            $count += Employee::whereNull('shift_id')
                ->whereHas('position', function ($query) use ($shift) {
                    $query->where('default_shift_id', $shift->id);
                })->count();

            // Count employees using this shift through department default
            $count += Employee::whereNull('shift_id')
                ->whereHas('position', function ($query) use ($shift) {
                    $query->whereNull('default_shift_id')
                        ->whereHas('department', function ($subQuery) use ($shift) {
                            $subQuery->where('default_shift_id', $shift->id);
                        });
                })->count();

            $percentage = $totalEmployees > 0 ? round(($count / $totalEmployees) * 100, 2) : 0;

            $tableData[] = [
                $shift->name,
                $count,
                $percentage . '%'
            ];
        }

        $this->table(
            ['Shift Name', 'Employee Count', 'Percentage'],
            $tableData
        );

        // Summary statistics
        $this->newLine();
        $this->info('📈 Summary:');
        $employeesWithOverride = Employee::whereNotNull('shift_id')->count();
        $employeesWithoutShift = Employee::whereNull('shift_id')
            ->whereDoesntHave('position', function ($query) {
                $query->whereNotNull('default_shift_id')
                    ->orWhereHas('department', function ($subQuery) {
                        $subQuery->whereNotNull('default_shift_id');
                    });
            })->count();

        $this->line("Total Employees: {$totalEmployees}");
        $this->line("Employees with Override: {$employeesWithOverride}");
        $this->line("Employees without Shift: {$employeesWithoutShift}");
    }
}
