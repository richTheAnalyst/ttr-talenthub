<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_manifest_is_valid_and_installable(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['start_url']);

        $sizes = array_column($manifest['icons'], 'sizes');
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_the_service_worker_and_offline_page_exist(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->get('/offline')->assertOk()->assertSee('You are offline');
    }

    public function test_pages_link_the_manifest_and_set_security_headers(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('viewport-fit=cover', false)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_the_service_worker_never_caches_personal_routes(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("'/cv/'", $sw);
        $this->assertStringContainsString("'/api/'", $sw);
    }
}
