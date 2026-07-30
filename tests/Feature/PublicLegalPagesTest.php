<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_is_publicly_accessible(): void
    {
        $this->get(route('privacy-policy'))
            ->assertOk()
            ->assertSee('Політика конфіденційності')
            ->assertSee('Facebook')
            ->assertSee('verification@korporatsiia-mov.com')
            ->assertDontSee('<style>', false);
    }

    public function test_data_deletion_instructions_are_publicly_accessible(): void
    {
        $this->get(route('data-deletion'))
            ->assertOk()
            ->assertSee('Видалення даних користувача')
            ->assertSee('Видалення даних')
            ->assertSee('30 календарних днів')
            ->assertDontSee('<style>', false);
    }

    public function test_public_navigation_links_to_legal_pages(): void
    {
        $this->get(route('index'))
            ->assertOk()
            ->assertSee(route('privacy-policy'), false)
            ->assertSee(route('data-deletion'), false)
            ->assertSee('class="site-footer"', false)
            ->assertSee('class="site-footer-grid"', false)
            ->assertSee('class="btn-register-label-mobile"', false)
            ->assertSee('class="navbar-toggler mobile-menu-toggle"', false)
            ->assertSee('class="navbar-toggler navbar-main-toggle"', false)
            ->assertSee('+38 (066) 299-22-18');
    }
}
