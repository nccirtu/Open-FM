<?php

it('redirects the home page to the Filament login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/pm/login');
});
