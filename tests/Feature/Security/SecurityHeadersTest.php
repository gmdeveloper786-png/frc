<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'security.headers_enabled' => true,
            'security.csp_enabled'     => true,
            'security.csp_report_only' => false,
            'security.hsts.enabled'    => true,
            'app.force_https'          => false,
        ]);
    }

    public function test_login_response_includes_security_headers_and_csp_nonce(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('nonce-', (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_hsts_header_is_sent_when_force_https_is_enabled(): void
    {
        config(['app.force_https' => true, 'security.hsts.enabled' => true]);

        $this->withoutMiddleware([\App\Http\Middleware\ForceHttps::class]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('Strict-Transport-Security');
    }
}
