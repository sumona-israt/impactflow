<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Curated default permission sets per the product brief's role
     * descriptions (§4). Super Administrator gets every permission (assigned
     * below, separately from this map) on top of its `Gate::before` bypass.
     * These are *defaults* — real deployments customize per-role permissions
     * from Administration → Roles & Permissions afterward; re-running this
     * seeder resets any such customization, so it's meant for fresh/demo
     * environments (`migrate:fresh --seed`), not a persisted production reset.
     *
     * @var array<string, list<PermissionEnum>>
     */
    private const DEFAULT_ROLE_PERMISSIONS = [
        RoleEnum::ProgramManager->value => [
            PermissionEnum::ViewAnyPrograms, PermissionEnum::ViewProgram,
            PermissionEnum::CreateProgram, PermissionEnum::UpdateProgram,
            PermissionEnum::UpdateProgramStatus, PermissionEnum::EnrollBeneficiaryInProgram,
            PermissionEnum::ViewAnyBeneficiaries, PermissionEnum::ViewBeneficiary,
            PermissionEnum::CreateBeneficiary, PermissionEnum::UpdateBeneficiary,
            PermissionEnum::ViewAnyActivities, PermissionEnum::CreateActivity,
            PermissionEnum::UpdateActivity, PermissionEnum::RecordActivityAttendance,
            PermissionEnum::ViewAnyDepartments, PermissionEnum::ViewAnyBranches,
            PermissionEnum::ViewAnyProgramCategories,
            // Reviews expenses as workflow step 1 (role_required, not a permission — §8)
            PermissionEnum::ViewAnyExpenses, PermissionEnum::ViewExpense,
            PermissionEnum::ViewAnyDataImports, PermissionEnum::CreateDataImport,
            PermissionEnum::ViewAnyDataQualityIssues, PermissionEnum::ManageDataQualityIssues,
        ],
        RoleEnum::FinanceOfficer->value => [
            PermissionEnum::ViewAnyPrograms, PermissionEnum::ViewProgram,
            PermissionEnum::ViewAnyDepartments, PermissionEnum::ViewAnyBranches,
            PermissionEnum::ViewAnyProgramCategories,
            PermissionEnum::ViewAnyExpenses, PermissionEnum::ViewExpense,
            PermissionEnum::ViewAnyExpenseCategories, PermissionEnum::ManageExpenseCategories,
            PermissionEnum::ViewAnyWorkflows, PermissionEnum::ViewAnyAssets,
        ],
        RoleEnum::FieldOfficer->value => [
            PermissionEnum::ViewAnyPrograms, PermissionEnum::ViewProgram,
            PermissionEnum::ViewAnyBeneficiaries, PermissionEnum::ViewBeneficiary,
            PermissionEnum::CreateBeneficiary, PermissionEnum::UpdateBeneficiary,
            PermissionEnum::ViewAnyActivities, PermissionEnum::CreateActivity,
            PermissionEnum::RecordActivityAttendance,
            PermissionEnum::ViewAnyDepartments, PermissionEnum::ViewAnyBranches,
            PermissionEnum::ViewAnyProgramCategories,
            PermissionEnum::ViewAnyExpenses, PermissionEnum::ViewExpense,
            PermissionEnum::CreateExpense, PermissionEnum::UpdateExpense,
            PermissionEnum::ViewAnyExpenseCategories,
            PermissionEnum::ViewAnyDataImports, PermissionEnum::CreateDataImport,
            PermissionEnum::ViewAnyDataQualityIssues, PermissionEnum::ManageDataQualityIssues,
        ],
        RoleEnum::HrAdminOfficer->value => [
            PermissionEnum::ViewAnyEmployees, PermissionEnum::CreateEmployee, PermissionEnum::UpdateEmployee,
            PermissionEnum::ViewAnyVolunteers, PermissionEnum::CreateVolunteer, PermissionEnum::UpdateVolunteer,
            PermissionEnum::ViewAnyDepartments, PermissionEnum::ManageDepartments,
            PermissionEnum::ViewAnyBranches, PermissionEnum::ManageBranches,
            PermissionEnum::ViewAnyAssets, PermissionEnum::CreateAsset,
            PermissionEnum::UpdateAsset, PermissionEnum::AssignAsset,
        ],
        RoleEnum::Management->value => [
            PermissionEnum::ViewAnyPrograms, PermissionEnum::ViewProgram,
            PermissionEnum::ViewAnyBeneficiaries, PermissionEnum::ViewBeneficiary,
            PermissionEnum::ViewAnyDepartments, PermissionEnum::ViewAnyBranches,
            PermissionEnum::ViewAnyProgramCategories,
            PermissionEnum::ViewAnyExpenses, PermissionEnum::ViewExpense,
            PermissionEnum::ViewAnyAssets, PermissionEnum::ViewAnyWorkflows,
            PermissionEnum::ViewAnyDataImports, PermissionEnum::ViewAnyDataQualityIssues,
            // Phase 6: the executive dashboard's staffing/activity KPIs need these.
            PermissionEnum::ViewAnyEmployees, PermissionEnum::ViewAnyVolunteers,
            PermissionEnum::ViewAnyActivities,
        ],
    ];

    public function run(): void
    {
        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        // syncPermissions() validates names against the registrar's cached
        // permission list, which findOrCreate() above doesn't invalidate.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findByName(RoleEnum::SuperAdmin->value, 'web')
            ->syncPermissions(array_map(fn (PermissionEnum $p) => $p->value, PermissionEnum::cases()));

        foreach (self::DEFAULT_ROLE_PERMISSIONS as $roleName => $permissions) {
            Role::findByName($roleName, 'web')
                ->syncPermissions(array_map(fn (PermissionEnum $p) => $p->value, $permissions));
        }
    }
}
