<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FrontPageTest extends TestCase
{
    #[Test]
    public function frontpage_pages_render()
    {
        $this->get('/')->assertOk()
            ->assertSee('front desk, sales, and support')
            ->assertSee('hero-dashboard.svg');

        $this->get('/about')->assertOk()->assertSee('About Nomdal');
        $this->get('/services')->assertOk()->assertSee('Our Services');
        $this->get('/contact')->assertOk()->assertSee('Contact Us');
        $this->get('/product')->assertOk()->assertSee('One platform for your AI workforce');
        $this->get('/templates')->assertOk()->assertSee('Hire an AI employee for every job');
        $this->get('/pricing')->assertOk()->assertSee('Simple, transparent pricing');
        $this->get('/docs')->assertOk()->assertSee('Documentation');
        $this->get('/connect')->assertOk()->assertSee('Works where your customers are');
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy');
        $this->get('/terms')->assertOk()->assertSee('Terms of Service');
    }
}
