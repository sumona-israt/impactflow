<?php

test('health endpoint reports ok when dependencies are reachable', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJson(['status' => 'ok'])
        ->assertJsonStructure(['status', 'checks' => ['database', 'redis'], 'timestamp']);
});
