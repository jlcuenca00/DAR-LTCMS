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
            ->assertSee('Skip to main content')
            ->assertSee('Transferor requirements')
            ->assertSee('Transferee requirements')
            ->assertSee('Additional / case-dependent')
            ->assertSee('Frequently asked questions')
            ->assertSee('No online self-application.')
            ->assertSee('DAR Negros Oriental Legal Assistance Division')
            ->assertSee('522-7144')
            ->assertSee('0916-876-3071')
            ->assertSee('dar_legal_orneg@yahoo.com')
            ->assertSee('(032) 253-6498')
            ->assertSee('https://www.facebook.com/DARLegalNegor', false)
            ->assertSee('https://www.dar.gov.ph/home', false)
            ->assertSee(route('login'), false);
    }
}
