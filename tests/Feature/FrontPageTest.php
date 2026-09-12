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
            ->assertSee('AI employees that run your');

        $this->get('/about')->assertOk()->assertSee('About Nomdal');
        $this->get('/services')->assertOk()->assertSee('Our Services');
        $this->get('/contact')->assertOk()->assertSee('autopilot');
        $this->get('/product')->assertOk()->assertSee('find customers');
        $this->get('/templates')->assertOk()->assertSee('Hire an AI employee for');
        $this->get('/pricing')->assertOk()->assertSee('Simple, transparent pricing');
        $this->get('/docs')->assertOk()->assertSee('Documentation');
        $this->get('/connect')->assertOk()->assertSee('Works where your customers are');
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy');
        $this->get('/terms')->assertOk()->assertSee('Terms of Service');
    }
}
