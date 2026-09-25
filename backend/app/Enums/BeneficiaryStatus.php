<?php

namespace App\Enums;

enum BeneficiaryStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Graduated = 'graduated';
}
