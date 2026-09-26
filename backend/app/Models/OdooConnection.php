<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OdooConnection extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
