<?php

namespace App\Actions\Activities;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Auth;

class CreateActivityAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(array $attributes): Activity
    {
        // See CreateProgramAction for why this is explicit, not a DB default.
        $activity = Activity::create([
            'status' => ActivityStatus::Scheduled,
            ...$attributes,
            'created_by' => Auth::id(),
        ]);

        $this->auditLogger->logCreated($activity, 'activity.created');

        return $activity;
    }
}
