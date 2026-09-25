<?php

namespace App\Models;

use App\Enums\BeneficiaryStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiary extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'full_name', 'date_of_birth', 'gender', 'phone', 'email', 'address',
        'district', 'upazila', 'status', 'registration_date',
        'emergency_contact_name', 'emergency_contact_phone', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'registration_date' => 'date',
            'status' => BeneficiaryStatus::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function beneficiaryPrograms(): HasMany
    {
        return $this->hasMany(BeneficiaryProgram::class);
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'beneficiary_programs')
            ->withPivot(['id', 'enrolled_at', 'status'])
            ->withTimestamps();
    }
}
