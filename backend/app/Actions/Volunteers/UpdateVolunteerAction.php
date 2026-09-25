<?php

namespace App\Actions\Volunteers;

use App\Models\Volunteer;
use App\Services\Audit\AuditLogger;

class UpdateVolunteerAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Volunteer $volunteer, array $attributes): Volunteer
    {
        $volunteer->update($attributes);

        if ($volunteer->wasChanged()) {
            $this->auditLogger->logUpdated($volunteer, 'volunteer.updated');
        }

        return $volunteer;
    }
}
