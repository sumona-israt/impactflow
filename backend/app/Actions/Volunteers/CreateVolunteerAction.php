<?php

namespace App\Actions\Volunteers;

use App\Enums\VolunteerStatus;
use App\Models\Volunteer;
use App\Services\Audit\AuditLogger;

class CreateVolunteerAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(array $attributes): Volunteer
    {
        // See CreateProgramAction for why this is explicit, not a DB default.
        $volunteer = Volunteer::create(['status' => VolunteerStatus::Active, ...$attributes]);

        $this->auditLogger->logCreated($volunteer, 'volunteer.created');

        return $volunteer;
    }
}
