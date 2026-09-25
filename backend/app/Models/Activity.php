<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['program_id', 'title', 'description', 'scheduled_at', 'location', 'status', 'created_by'];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => ActivityStatus::class,
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(ActivityAttendance::class);
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(Beneficiary::class, 'activity_attendance')
            ->withPivot(['id', 'attended'])
            ->withTimestamps();
    }
}
