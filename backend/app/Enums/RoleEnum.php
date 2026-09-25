<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SuperAdmin = 'super-admin';
    case ProgramManager = 'program-manager';
    case FinanceOfficer = 'finance-officer';
    case FieldOfficer = 'field-officer';
    case HrAdminOfficer = 'hr-admin-officer';
    case Management = 'management';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrator',
            self::ProgramManager => 'Program Manager',
            self::FinanceOfficer => 'Finance Officer',
            self::FieldOfficer => 'Field Officer',
            self::HrAdminOfficer => 'HR/Admin Officer',
            self::Management => 'Management',
        };
    }
}
