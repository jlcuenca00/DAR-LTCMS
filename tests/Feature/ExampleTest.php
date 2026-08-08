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
            ->assertSee('Land transfer clearance, made easier to understand and monitor.')
            ->assertSee('Transferor requirements')
            ->assertSee('Transferee requirements')
            ->assertSee('Case-dependent or additional documents')
            ->assertSee('Frequently asked questions')
            ->assertSee('No online self-application.')
            ->assertSee('Clearance approval and generation through DAR-LTCMS does not itself execute the legal transfer of land ownership')
            ->assertSee(route('login'), false);
    }
}
