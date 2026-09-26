<?php

use App\Models\User;

test('the 6th rapid failed login attempt for the same email is rate limited', function () {
    $user = User::factory()->create(['password' => 'correct-password']);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

test('a lockout on one email does not block a different email from the same client', function () {
    $lockedOutUser = User::factory()->create(['password' => 'correct-password']);
    $otherUser = User::factory()->create(['password' => 'correct-password']);

    for ($i = 0; $i < 6; $i++) {
        $this->postJson('/api/v1/login', [
            'email' => $lockedOutUser->email,
            'password' => 'wrong-password',
        ]);
    }

    $this->postJson('/api/v1/login', [
        'email' => $lockedOutUser->email,
        'password' => 'wrong-password',
    ])->assertStatus(429);

    // Different email, same client — the limiter key is email+IP, not IP
    // alone, so this must still be processed normally (422, not 429).
    $this->postJson('/api/v1/login', [
        'email' => $otherUser->email,
        'password' => 'wrong-password',
    ])->assertUnprocessable();
});
