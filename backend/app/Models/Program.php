<?php

namespace App\Models;

use App\Enums\ProgramStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'category_id', 'manager_id', 'branch_id',
        'district', 'upazila', 'start_date', 'end_date', 'status',
        'budget', 'target_beneficiaries', 'progress', 'created_by', 'odoo_project_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ProgramStatus::class,
            'budget' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProgramCategory::class, 'category_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function beneficiaryPrograms(): HasMany
    {
        return $this->hasMany(BeneficiaryProgram::class);
    }

    public function beneficiaries(): BelongsToMany
    {
        return $this->belongsToMany(Beneficiary::class, 'beneficiary_programs')
            ->withPivot(['id', 'enrolled_at', 'status'])
            ->withTimestamps();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /** Live count, not stored — see docs/database-design.md §3. */
    public function getActualBeneficiariesAttribute(): int
    {
        return $this->beneficiaryPrograms()->where('status', '!=', 'withdrawn')->count();
    }
}
