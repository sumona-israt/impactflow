<?php

use App\Models\Beneficiary;
use App\Models\Program;
use App\Services\Odoo\OdooMappingService;

beforeEach(function () {
    $this->mapping = new OdooMappingService;
});

test('buildPayload maps a Program to its configured Odoo fields, no DB round-trip needed', function () {
    $program = new Program([
        'name' => 'Clean Water Access',
        'description' => 'WASH program',
        'district' => 'Dhaka',
    ]);

    $payload = $this->mapping->buildPayload($program);

    expect($payload)->toBe([
        'name' => 'Clean Water Access',
        'description' => 'WASH program',
        'x_district' => 'Dhaka',
    ]);
});

test('buildPayload maps a Beneficiary to its configured Odoo fields', function () {
    $beneficiary = new Beneficiary([
        'full_name' => 'Jane Doe',
        'phone' => '01700000000',
        'address' => '123 Test Road',
    ]);

    $payload = $this->mapping->buildPayload($beneficiary);

    expect($payload)->toBe([
        'name' => 'Jane Doe',
        'phone' => '01700000000',
        'street' => '123 Test Road',
    ]);
});

test('odooModelFor and slugFor return the configured values for each mapped entity', function () {
    expect($this->mapping->odooModelFor(Program::class))->toBe('project.project');
    expect($this->mapping->slugFor(Program::class))->toBe('program');

    expect($this->mapping->odooModelFor(Beneficiary::class))->toBe('res.partner');
    expect($this->mapping->slugFor(Beneficiary::class))->toBe('beneficiary');
});

test('entityTypeForSlug resolves a known slug back to its FQCN', function () {
    expect($this->mapping->entityTypeForSlug('program'))->toBe(Program::class);
    expect($this->mapping->entityTypeForSlug('beneficiary'))->toBe(Beneficiary::class);
});

test('entityTypeForSlug throws on an unknown slug', function () {
    expect(fn () => $this->mapping->entityTypeForSlug('not-a-real-entity'))
        ->toThrow(RuntimeException::class, 'Unknown Odoo entity slug: not-a-real-entity');
});

test('odooModelFor throws for an entity with no configured mapping', function () {
    expect(fn () => $this->mapping->odooModelFor(stdClass::class))
        ->toThrow(RuntimeException::class);
});
