<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OdooMapping extends Model
{
    protected $fillable = ['entity_type', 'local_id', 'odoo_model', 'odoo_id'];
}
