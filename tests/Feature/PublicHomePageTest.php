<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicHomePageTest extends TestCase
{
    public function test_public_home_page_presents_the_current_product_and_growth_path(): void
    {
        $this
            ->get('/')
            ->assertOk()
            ->assertSee('Zephrant ERP')
            ->assertSee('Billing, inventory, purchases, customer accounts, supplier balances')
            ->assertSee('General Stores')
            ->assertSee('Hardware Shops')
            ->assertSee('Pharmacies')
            ->assertSee('Wholesalers')
            ->assertSee('Built to grow beyond retail')
            ->assertSee('Hospital, hotel, and other industry-specific management services')
            ->assertSee(route('plans.index'), false)
            ->assertSee(asset('images/zephrant-erp-home-hero.png'), false)
            ->assertDontSee('portfolio', false);

        $this->assertFileExists(public_path('images/zephrant-erp-home-hero.png'));
    }
}
