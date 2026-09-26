<?php

namespace App\Enums;

enum DataQualityIssueStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
    case Ignored = 'ignored';
}
