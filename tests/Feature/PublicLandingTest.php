<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicLandingTest extends TestCase
{
    public function test_public_landing_page_presents_billstack_positioning(): void
    {
        $this
            ->get('/')
            ->assertOk()
            ->assertSee('BillStack')
            ->assertSee('Inventory and billing SaaS')
            ->assertSee('A business management system')
            ->assertSee('Built for real shop workflows')
            ->assertSee('Create Demo Account');
    }
}
