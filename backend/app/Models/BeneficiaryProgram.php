<?php

namespace App\Models;

use App\Enums\BeneficiaryProgramStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryProgram extends Model
{
    protected $fillable = ['beneficiary_id', 'program_id', 'enrolled_at', 'status'];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'status' => BeneficiaryProgramStatus::class,
        ];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
