<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\ApprovalWorkflow;
use App\Models\Expense;
use Illuminate\Database\Seeder;

/**
 * The one workflow definition Phase 4 ships: Expense Approval
 * (Program Review -> Finance Review). Definitions aren't admin-editable yet
 * — see docs/database-design.md §8 for why.
 */
class ApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $workflow = ApprovalWorkflow::firstOrCreate(
            ['name' => 'Expense Approval'],
            ['entity_type' => Expense::class],
        );

        $workflow->steps()->firstOrCreate(
            ['sequence' => 1],
            ['name' => 'program_review', 'role_required' => RoleEnum::ProgramManager->value],
        );

        $workflow->steps()->firstOrCreate(
            ['sequence' => 2],
            ['name' => 'finance_review', 'role_required' => RoleEnum::FinanceOfficer->value],
        );
    }
}
