<?php

use App\Enums\RoleEnum;
use App\Models\Beneficiary;
use App\Models\DataQualityIssue;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->fieldOfficer = User::factory()->create();
    $this->fieldOfficer->assignRole(RoleEnum::FieldOfficer->value);

    $this->financeOfficer = User::factory()->create();
    $this->financeOfficer->assignRole(RoleEnum::FinanceOfficer->value);

    Storage::fake('local');
});

function beneficiaryCsv(): UploadedFile
{
    $content = implode("\n", [
        'Name,Phone,Registered On',
        // Matches an existing beneficiary — duplicate row
        'Tania Rahman,01712340001,2024-01-05',
        // No match — valid row
        'Fahim Islam,01712340009,2024-02-01',
        // Missing name — invalid row
        ',01712340002,2024-01-06',
    ])."\n";

    return UploadedFile::fake()->createWithContent('beneficiaries.csv', $content);
}

test('a field officer can walk a CSV import through the full pipeline', function () {
    $existing = Beneficiary::factory()->create(['full_name' => 'Tania Rahman', 'phone' => '01712340001']);

    $upload = $this->actingAs($this->fieldOfficer)
        ->postJson('/api/v1/imports', ['file' => beneficiaryCsv(), 'entity_type' => 'beneficiaries'])
        ->assertCreated();

    $importId = $upload->json('data.id');
    expect($upload->json('data.detected_headers'))->toBe(['Name', 'Phone', 'Registered On']);
    expect($upload->json('data.status'))->toBe('uploaded');

    $this->actingAs($this->fieldOfficer)
        ->putJson("/api/v1/imports/{$importId}/mapping", [
            'mapping' => ['full_name' => 'Name', 'phone' => 'Phone', 'registration_date' => 'Registered On'],
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'mapped');

    $preview = $this->actingAs($this->fieldOfficer)
        ->postJson("/api/v1/imports/{$importId}/preview")
        ->assertOk();

    expect($preview->json('data.status'))->toBe('previewed');
    expect($preview->json('data.total_rows'))->toBe(3);
    expect($preview->json('data.valid_rows'))->toBe(1);
    expect($preview->json('data.duplicate_rows'))->toBe(1);
    expect($preview->json('data.invalid_rows'))->toBe(1);

    $rows = $this->actingAs($this->fieldOfficer)
        ->getJson("/api/v1/imports/{$importId}/preview")
        ->assertOk();

    expect(collect($rows->json('data'))->pluck('status')->all())->toEqualCanonicalizing(['valid', 'duplicate', 'invalid']);

    $beneficiariesBefore = Beneficiary::count();

    $this->actingAs($this->fieldOfficer)
        ->postJson("/api/v1/imports/{$importId}/commit")
        ->assertOk()
        ->assertJsonPath('data.status', 'committed');

    // The valid row and the duplicate row are both created (duplicates are
    // flagged, not blocked); the invalid rows are not.
    expect(Beneficiary::count())->toBe($beneficiariesBefore + 2);

    $duplicateIssue = DataQualityIssue::query()
        ->where('issue_type', 'duplicate_beneficiary')
        ->where('description', 'like', '%'.$existing->full_name.'%')
        ->first();

    expect($duplicateIssue)->not->toBeNull();
});

test('an xlsx file is parsed the same way as csv', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(['Name', 'Phone', 'Registered On'], null, 'A1');
    $sheet->fromArray(['Fahim Islam', '01712340009', '2024-02-01'], null, 'A2');

    $tmp = tempnam(sys_get_temp_dir(), 'import').'.xlsx';
    (new Xlsx($spreadsheet))->save($tmp);
    $file = new UploadedFile($tmp, 'beneficiaries.xlsx', null, null, true);

    $upload = $this->actingAs($this->fieldOfficer)
        ->postJson('/api/v1/imports', ['file' => $file, 'entity_type' => 'beneficiaries'])
        ->assertCreated();

    expect($upload->json('data.detected_headers'))->toBe(['Name', 'Phone', 'Registered On']);

    @unlink($tmp);
});

test('a finance officer cannot upload an import', function () {
    $this->actingAs($this->financeOfficer)
        ->postJson('/api/v1/imports', ['file' => beneficiaryCsv(), 'entity_type' => 'beneficiaries'])
        ->assertForbidden();
});

test('a non spreadsheet file is rejected', function () {
    $this->actingAs($this->fieldOfficer)
        ->postJson('/api/v1/imports', [
            'file' => UploadedFile::fake()->create('not-a-spreadsheet.pdf', 10, 'application/pdf'),
            'entity_type' => 'beneficiaries',
        ])
        ->assertUnprocessable();
});

test('commit is blocked before the import has been previewed', function () {
    $upload = $this->actingAs($this->fieldOfficer)
        ->postJson('/api/v1/imports', ['file' => beneficiaryCsv(), 'entity_type' => 'beneficiaries'])
        ->assertCreated();

    $this->actingAs($this->fieldOfficer)
        ->postJson("/api/v1/imports/{$upload->json('data.id')}/commit")
        ->assertUnprocessable();
});

test('mapping without the required fields is rejected', function () {
    $upload = $this->actingAs($this->fieldOfficer)
        ->postJson('/api/v1/imports', ['file' => beneficiaryCsv(), 'entity_type' => 'beneficiaries'])
        ->assertCreated();

    $this->actingAs($this->fieldOfficer)
        ->putJson("/api/v1/imports/{$upload->json('data.id')}/mapping", [
            'mapping' => ['phone' => 'Phone'],
        ])
        ->assertUnprocessable();
});
