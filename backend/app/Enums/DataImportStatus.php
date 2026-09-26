<?php

namespace App\Enums;

enum DataImportStatus: string
{
    case Uploaded = 'uploaded';
    case Mapped = 'mapped';
    case Previewed = 'previewed';
    case Committing = 'committing';
    case Committed = 'committed';
    case Failed = 'failed';
}
