<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_is_not_rendered_without_a_measurement_id(): void
    {
        config()->set('services.google_analytics.measurement_id');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com/gtag/js', false)
            ->assertDontSee('data-cookie-consent', false);
    }

    public function test_public_page_renders_consent_mode_and_analytics_hooks(): void
    {
        config()->set('services.google_analytics.measurement_id', 'G-TEST123456');

        $this->get('/')
            ->assertOk()
            ->assertSee('googletagmanager.com/gtag/js?id=G-TEST123456', false)
            ->assertSee("analytics_storage: 'denied'", false)
            ->assertSee('data-cookie-consent', false)
            ->assertSee('data-analytics-event="view_trial_lesson_form"', false)
            ->assertSee('data-analytics-event="contact"', false);
    }

    public function test_private_page_does_not_send_page_views(): void
    {
        config()->set('services.google_analytics.measurement_id', 'G-TEST123456');

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('googletagmanager.com/gtag/js', false);
    }

    public function test_search_console_verification_is_configurable(): void
    {
        config()->set('services.google_search_console.verification', 'verification-token');

        $this->get('/')
            ->assertOk()
            ->assertSee('name="google-site-verification" content="verification-token"', false);
    }

    public function test_server_event_can_be_sent_without_enabling_private_page_views(): void
    {
        config()->set('services.google_analytics.measurement_id', 'G-TEST123456');

        $this->withSession([
            'analytics_event' => [
                'name' => 'purchase',
                'parameters' => [
                    'transaction_id' => 'payment-10',
                    'value' => 3200,
                    'currency' => 'UAH',
                ],
            ],
        ])->get('/login')
            ->assertOk()
            ->assertSee('googletagmanager.com/gtag/js?id=G-TEST123456', false)
            ->assertSee('"name":"purchase"', false)
            ->assertSee('"transaction_id":"payment-10"', false)
            ->assertSee('send_page_view: false', false);
    }
}
