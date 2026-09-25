<?php

namespace App\Enums;

enum VolunteerAvailability: string
{
    case Weekdays = 'weekdays';
    case Weekends = 'weekends';
    case Evenings = 'evenings';
    case FullTime = 'full_time';
}
