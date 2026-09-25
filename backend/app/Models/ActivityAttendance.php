<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAttendance extends Model
{
    // Eloquent's naive pluralization would guess "activity_attendances";
    // the migration (and docs/database-design.md §6) use the singular form.
    protected $table = 'activity_attendance';

    protected $fillable = ['activity_id', 'beneficiary_id', 'attended'];

    protected function casts(): array
    {
        return ['attended' => 'boolean'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
