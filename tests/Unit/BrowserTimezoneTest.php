<?php

namespace Tests\Unit;

use App\Services\BrowserTimezone;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrowserTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_timestamp_is_formatted_in_the_browser_timezone(): void
    {
        Session::put('browser_timezone', 'Asia/Makassar');

        $formatted = app(BrowserTimezone::class)->format(
            Carbon::create(2026, 10, 4, 0, 0, 0, 'UTC'),
            'Y-m-d H:i T',
        );

        $this->assertSame('2026-10-04 08:00 WITA', $formatted);
    }

    public function test_invalid_browser_timezone_uses_the_application_fallback(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);
        Session::put('browser_timezone', 'Not/A-Timezone');

        $formatted = app(BrowserTimezone::class)->format(
            Carbon::create(2026, 10, 4, 0, 0, 0, 'UTC'),
            'Y-m-d H:i T',
        );

        $this->assertSame('2026-10-04 07:00 WIB', $formatted);
    }

    public function test_browser_timezone_endpoint_stores_a_valid_timezone(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->postJson(route('cashier.browser-timezone'), [
            'timezone' => 'Asia/Jakarta',
        ]);

        $response->assertOk()->assertJson(['updated' => true]);
        $this->assertSame('Asia/Jakarta', session('browser_timezone'));
    }
}
