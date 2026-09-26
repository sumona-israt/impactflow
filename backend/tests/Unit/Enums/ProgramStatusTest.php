<?php

use App\Enums\ProgramStatus;

test('allowedNextStatuses matches the real state machine for every case', function () {
    $expected = [
        ProgramStatus::Draft->value => [ProgramStatus::PendingApproval, ProgramStatus::Archived],
        ProgramStatus::PendingApproval->value => [ProgramStatus::Approved, ProgramStatus::Draft],
        ProgramStatus::Approved->value => [ProgramStatus::Active, ProgramStatus::Archived],
        ProgramStatus::Active->value => [ProgramStatus::Paused, ProgramStatus::Completed, ProgramStatus::Archived],
        ProgramStatus::Paused->value => [ProgramStatus::Active, ProgramStatus::Archived],
        ProgramStatus::Completed->value => [ProgramStatus::Archived],
        ProgramStatus::Archived->value => [],
    ];

    foreach (ProgramStatus::cases() as $status) {
        expect($status->allowedNextStatuses())->toEqual($expected[$status->value]);
    }
});

test('every case not in the allowed list is genuinely disallowed', function (ProgramStatus $status) {
    $allowed = $status->allowedNextStatuses();
    $disallowed = array_filter(ProgramStatus::cases(), fn (ProgramStatus $other) => ! in_array($other, $allowed, true) && $other !== $status);

    foreach ($disallowed as $other) {
        expect($allowed)->not->toContain($other);
    }
})->with(ProgramStatus::cases());

test('a terminal status (Archived) allows no further transitions', function () {
    expect(ProgramStatus::Archived->allowedNextStatuses())->toBe([]);
});
