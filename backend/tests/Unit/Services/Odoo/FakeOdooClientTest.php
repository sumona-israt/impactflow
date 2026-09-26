<?php

use App\Exceptions\OdooConnectionException;
use App\Services\Odoo\FakeOdooClient;

beforeEach(function () {
    config(['odoo.mock_failure_rate' => 0]);
    $this->client = new FakeOdooClient;
});

test('authenticate always returns the fixed fake uid', function () {
    expect($this->client->authenticate())->toBe(1);
});

test('create is deterministic for the same model and payload', function () {
    $payload = ['name' => 'Clean Water Program', 'x_district' => 'Dhaka'];

    $first = $this->client->callKw('project.project', 'create', [$payload]);
    $second = $this->client->callKw('project.project', 'create', [$payload]);

    expect($first)->toBe($second);
    expect($first)->toBeInt();
});

test('create resolves to a different id for a different payload', function () {
    $idA = $this->client->callKw('project.project', 'create', [['name' => 'Program A']]);
    $idB = $this->client->callKw('project.project', 'create', [['name' => 'Program B']]);

    expect($idA)->not->toBe($idB);
});

test('write returns true and never a fake id', function () {
    expect($this->client->callKw('project.project', 'write', [[123], ['name' => 'Renamed']]))->toBeTrue();
});

test('a zero failure rate never throws', function () {
    config(['odoo.mock_failure_rate' => 0]);

    for ($i = 0; $i < 20; $i++) {
        $this->client->callKw('project.project', 'create', [['name' => "Program {$i}"]]);
    }

    expect(true)->toBeTrue();
});

test('a failure rate of 1 always throws OdooConnectionException', function () {
    config(['odoo.mock_failure_rate' => 1]);

    expect(fn () => $this->client->callKw('project.project', 'create', [['name' => 'Any']]))
        ->toThrow(OdooConnectionException::class);
});
