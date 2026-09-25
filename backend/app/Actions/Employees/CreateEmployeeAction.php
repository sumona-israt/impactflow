<?php

namespace App\Actions\Employees;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Services\Audit\AuditLogger;

class CreateEmployeeAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(array $attributes): Employee
    {
        // See CreateProgramAction for why this is explicit, not a DB default.
        $employee = Employee::create(['status' => EmployeeStatus::Active, ...$attributes]);

        $this->auditLogger->logCreated($employee, 'employee.created');

        return $employee;
    }
}
