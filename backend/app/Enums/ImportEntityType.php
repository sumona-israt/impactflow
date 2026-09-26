<?php

namespace App\Enums;

/**
 * Which import pipeline a `data_imports` row runs — not a polymorphic morph
 * target (a single import creates many records), just a pipeline selector.
 * Only Beneficiaries this phase; see docs/database-design.md §9.
 */
enum ImportEntityType: string
{
    case Beneficiaries = 'beneficiaries';
}
