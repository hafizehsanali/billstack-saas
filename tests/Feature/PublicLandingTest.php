<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicLandingTest extends TestCase
{
    public function test_public_landing_page_presents_zephrant_erp_positioning(): void
    {
        $this
            ->get('/')
            ->assertOk()
            ->assertSee('Zephrant ERP')
            ->assertSee('Technology That Drives Growth')
            ->assertSee('Billing, inventory, purchases, customer accounts')
            ->assertSee('Know what is selling, what is due, and what needs attention')
            ->assertSee('View Packages');
    }
}
