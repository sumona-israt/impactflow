<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['user_id', 'name', 'department_id', 'branch_id', 'position', 'joining_date', 'status', 'odoo_employee_id'];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'status' => EmployeeStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
