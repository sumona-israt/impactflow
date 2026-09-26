<?php

test('the storage file-serving route is not registered, since the local disk is never web-served', function () {
    $this->get('/storage/anything')->assertNotFound();
});
