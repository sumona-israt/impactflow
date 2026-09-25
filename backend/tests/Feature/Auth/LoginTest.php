<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::findOrCreate($role->value, 'web');
    }
});

/**
 * Sanctum only treats a request as a same-origin SPA request (and therefore
 * boots the session) when its Referer matches a configured stateful domain
 * (see docs/architecture.md §3) — real browser requests from the Next.js
 * frontend always carry this header, so tests set it explicitly to exercise
 * the actual cookie-session login flow rather than the token-guard path.
 */
function fromFrontend()
{
    return test()->withHeader('Referer', config('app.url'));
}

test('a user can log in with valid credentials and receives their roles', function () {
    $user = User::factory()->create(['password' => 'correct-password']);
    $user->assignRole(RoleEnum::ProgramManager->value);

    $response = fromFrontend()->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.roles.0', RoleEnum::ProgramManager->value);

    $this->assertAuthenticatedAs($user);
});

test('login fails with invalid credentials', function () {
    $user = User::factory()->create(['password' => 'correct-password']);

    $response = fromFrontend()->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->assertGuest();
});

test('an inactive user cannot log in', function () {
    $user = User::factory()->create([
        'password' => 'correct-password',
        'is_active' => false,
    ]);

    $response = fromFrontend()->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertUnprocessable();
    $this->assertGuest();
});

test('an authenticated user can fetch their profile and log out', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FieldOfficer->value);

    $this->actingAs($user)
        ->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);

    fromFrontend()->actingAs($user)
        ->postJson('/api/v1/logout')
        ->assertOk();
});

test('a guest cannot access the authenticated user endpoint', function () {
    $this->getJson('/api/v1/user')->assertUnauthorized();
});
