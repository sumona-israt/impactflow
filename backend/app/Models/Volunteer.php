<?php

namespace App\Models;

use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Volunteer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['full_name', 'phone', 'email', 'skills', 'availability', 'status'];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'availability' => VolunteerAvailability::class,
            'status' => VolunteerStatus::class,
        ];
    }
}
