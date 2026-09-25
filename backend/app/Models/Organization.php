<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = ['name', 'registration_number', 'address'];

    /**
     * Single-row in practice (see docs/database-design.md §2) — the one
     * place that assumption is encoded, so a future multi-org change only
     * touches call sites of this method, not a hardcoded id everywhere.
     */
    public static function current(): self
    {
        return self::query()->firstOrFail();
    }
}
