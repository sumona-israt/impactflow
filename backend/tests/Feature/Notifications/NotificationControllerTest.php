<?php

use App\Models\Program;
use App\Models\User;
use App\Notifications\ProgramAwaitingApproval;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

test('a user can list, count, and mark their own notifications as read', function () {
    $program = Program::factory()->create();
    $this->user->notify(new ProgramAwaitingApproval($program));

    $response = $this->actingAs($this->user)->getJson('/api/v1/notifications')->assertOk();
    expect($response->json('data'))->toHaveCount(1);

    $this->actingAs($this->user)
        ->getJson('/api/v1/notifications/unread-count')
        ->assertJsonPath('data.count', 1);

    $notificationId = $response->json('data.0.id');

    $this->actingAs($this->user)
        ->postJson("/api/v1/notifications/{$notificationId}/read")
        ->assertOk();

    $this->actingAs($this->user)
        ->getJson('/api/v1/notifications/unread-count')
        ->assertJsonPath('data.count', 0);
});

test("a user cannot mark another user's notification as read", function () {
    $program = Program::factory()->create();
    $this->user->notify(new ProgramAwaitingApproval($program));
    $notificationId = $this->user->notifications()->first()->id;

    $this->actingAs($this->otherUser)
        ->postJson("/api/v1/notifications/{$notificationId}/read")
        ->assertNotFound();

    expect($this->user->unreadNotifications()->count())->toBe(1);
});

test('mark-all-as-read marks every unread notification for the caller only', function () {
    $program = Program::factory()->create();
    $this->user->notify(new ProgramAwaitingApproval($program));
    $this->user->notify(new ProgramAwaitingApproval($program));
    $this->otherUser->notify(new ProgramAwaitingApproval($program));

    $this->actingAs($this->user)->postJson('/api/v1/notifications/read-all')->assertOk();

    expect($this->user->unreadNotifications()->count())->toBe(0);
    expect($this->otherUser->unreadNotifications()->count())->toBe(1);
});
