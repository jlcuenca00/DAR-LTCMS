<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_public_landing_page_is_available(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Land Transfer Clearance and Monitoring System')
            ->assertSee('Authorized User Login')
            ->assertSee(route('login'), false);
    }
}
