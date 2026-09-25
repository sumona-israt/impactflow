<?php

test('an unauthenticated browser request to a protected route redirects to the frontend login page instead of erroring', function () {
    // No "login" named route exists in this API-only app (the login page is
    // the Next.js frontend's /login) — this guards against a
    // RouteNotFoundException regression (see bootstrap/app.php).
    $response = $this->get('/api/v1/user');

    $response->assertRedirect('/login');
});

test('an unauthenticated JSON request to a protected route gets a 401 instead of a redirect', function () {
    $response = $this->getJson('/api/v1/user');

    $response->assertUnauthorized();
});
