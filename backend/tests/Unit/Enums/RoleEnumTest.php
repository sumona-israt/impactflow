<?php

use App\Enums\RoleEnum;

test('every role case has a non-empty, human-readable label', function (RoleEnum $role) {
    expect($role->label())->toBeString()->not->toBeEmpty();
    expect($role->label())->not->toBe($role->value);
})->with(RoleEnum::cases());

test('labels are unique across all roles', function () {
    $labels = array_map(fn (RoleEnum $role) => $role->label(), RoleEnum::cases());

    expect($labels)->toEqual(array_unique($labels));
});
