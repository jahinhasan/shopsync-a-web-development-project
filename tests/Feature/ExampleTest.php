<?php

it('shows guests the landing page from the home page', function () {
    $response = $this->get('/');

    $response->assertOk();
});
