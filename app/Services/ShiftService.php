<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\Department;
use App\Models\Position;

class ShiftService
{
    /**
     * Get effective shift for an employee using the hierarchy fallback.
     * 
     * @param Employee $employee
     * @return Shift|null
     */
    public function getEffectiveShiftForEmployee(Employee $employee): ?Shift
    {
        return $employee->getEffectiveShift();
    }

    /**
     * Set employee specific shift (override).
     * 
     * @param Employee $employee
     * @param Shift|null $shift
     * @return bool
     */
    public function setEmployeeShift(Employee $employee, ?Shift $shift): bool
    {
        $employee->shift_id = $shift?->id;
        return $employee->save();
    }

    /**
     * Set default shift for a position.
     * 
     * @param Position $position
     * @param Shift|null $shift
     * @return bool
     */
    public function setPositionDefaultShift(Position $position, ?Shift $shift): bool
    {
        $position->default_shift_id = $shift?->id;
        return $position->save();
    }

    /**
     * Set default shift for a department.
     * 
     * @param Department $department
     * @param Shift|null $shift
     * @return bool
     */
    public function setDepartmentDefaultShift(Department $department, ?Shift $shift): bool
    {
        $department->default_shift_id = $shift?->id;
        return $department->save();
    }

    /**
     * Get all employees affected by a position shift change.
     * 
     * @param Position $position
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEmployeesAffectedByPositionShift(Position $position)
    {
        return $position->employees()
            ->whereNull('shift_id') // Only employees without specific shift override
            ->with(['position.defaultShift', 'position.department.defaultShift'])
            ->get();
    }

    /**
     * Get all employees affected by a department shift change.
     * 
     * @param Department $department
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEmployeesAffectedByDepartmentShift(Department $department)
    {
        return Employee::whereHas('position', function ($query) use ($department) {
            $query->where('department_id', $department->id)
                ->whereNull('default_shift_id'); // Only positions without specific shift
        })
            ->whereNull('shift_id') // Only employees without specific shift override
            ->with(['position.defaultShift', 'position.department.defaultShift'])
            ->get();
    }

    /**
     * Get shift statistics for reporting.
     * 
     * @return array
     */
    public function getShiftStatistics(): array
    {
        $totalEmployees = Employee::count();
        $employeesWithOverride = Employee::whereNotNull('shift_id')->count();
        $employeesUsingPositionDefault = Employee::whereNull('shift_id')
            ->whereHas('position', function ($query) {
                $query->whereNotNull('default_shift_id');
            })->count();
        $employeesUsingDepartmentDefault = Employee::whereNull('shift_id')
            ->whereHas('position', function ($query) {
                $query->whereNull('default_shift_id')
                    ->whereHas('department', function ($subQuery) {
                        $subQuery->whereNotNull('default_shift_id');
                    });
            })->count();
        $employeesWithoutShift = $totalEmployees - $employeesWithOverride - $employeesUsingPositionDefault - $employeesUsingDepartmentDefault;

        return [
            'total_employees' => $totalEmployees,
            'employees_with_override' => $employeesWithOverride,
            'employees_using_position_default' => $employeesUsingPositionDefault,
            'employees_using_department_default' => $employeesUsingDepartmentDefault,
            'employees_without_shift' => $employeesWithoutShift,
            'shift_distribution' => $this->getShiftDistribution()
        ];
    }

    /**
     * Get distribution of employees across shifts.
     * 
     * @return array
     */
    private function getShiftDistribution(): array
    {
        $shifts = Shift::all();
        $distribution = [];

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

            $distribution[] = [
                'shift_name' => $shift->name,
                'employee_count' => $count,
                'percentage' => Employee::count() > 0 ? round(($count / Employee::count()) * 100, 2) : 0
            ];
        }

        return $distribution;
    }
}
