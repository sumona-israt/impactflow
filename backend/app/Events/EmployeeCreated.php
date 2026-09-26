<?php

namespace App\Events;

use App\Models\Employee;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when an employee is created (see
 * App\Actions\Employees\CreateEmployeeAction). Consumed by
 * App\Listeners\DispatchEmployeeCreatedToOdoo.
 */
class EmployeeCreated
{
    use Dispatchable;

    public function __construct(public readonly Employee $employee) {}
}
