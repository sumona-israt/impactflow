<?php

namespace App\Actions\Activities;

use App\Models\Activity;
use App\Services\Audit\AuditLogger;

class UpdateActivityAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Activity $activity, array $attributes): Activity
    {
        $activity->update($attributes);

        if ($activity->wasChanged()) {
            $this->auditLogger->logUpdated($activity, 'activity.updated');
        }

        return $activity;
    }
}
