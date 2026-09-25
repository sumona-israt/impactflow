<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Terminated = 'terminated';
}
