<?php

namespace App\Actions\Employees;

use App\Models\Employee;
use App\Services\Audit\AuditLogger;

class UpdateEmployeeAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Employee $employee, array $attributes): Employee
    {
        $employee->update($attributes);

        if ($employee->wasChanged()) {
            $this->auditLogger->logUpdated($employee, 'employee.updated');
        }

        return $employee;
    }
}
