<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    // RefreshDatabase rolls back each test's DB rows, but Spatie's permission
    // registrar caches roles/permissions in a process-wide singleton that
    // survives the rollback — without forgetting it, a test can see another
    // test's (rolled-back) permission set depending on run order.
    ->beforeEach(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions())
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');
