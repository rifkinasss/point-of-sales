<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_public_landing_page_uses_the_canonical_login_route(): void
    {
        $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Operasional bisnis, dalam satu ruang kerja.')
        ->assertSee(route('auth.login'), false)
        ->assertSee('data-scroll-target="produk"', false)
        ->assertSee('data-scroll-target="cara-kerja"', false)
        ->assertSee('data-scroll-target="workspace"', false)
        ->assertSee('Platform operasional bisnis.', false)
        ->assertSee('Masuk ke NasLabs ERP', false)
        ->assertSee(route('auth.login'), false)
        ->assertSee(route('landing'), false)
        ->assertDontSee('href="#produk"', false)
        ->assertDontSee('href="#cara-kerja"', false)
        ->assertDontSee('href="#workspace"', false)
        ->assertDontSee('href="#top"', false);
}
}
