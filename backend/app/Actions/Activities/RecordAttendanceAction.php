<?php

namespace App\Actions\Activities;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Collection;

class RecordAttendanceAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, bool>  $attendance  beneficiary_id => attended
     * @return Collection<int, ActivityAttendance>
     */
    public function execute(Activity $activity, array $attendance): Collection
    {
        $records = collect($attendance)->map(
            fn (bool $attended, string $beneficiaryId) => ActivityAttendance::updateOrCreate(
                ['activity_id' => $activity->id, 'beneficiary_id' => $beneficiaryId],
                ['attended' => $attended],
            )
        )->values();

        $this->auditLogger->log(
            'activity.attendance_recorded',
            Activity::class,
            $activity->id,
            [],
            ['beneficiary_count' => $records->count(), 'attended_count' => $records->where('attended', true)->count()],
        );

        return $records;
    }
}
