<?php

namespace App\Enums;

enum BeneficiaryProgramStatus: string
{
    case Enrolled = 'enrolled';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';
}
