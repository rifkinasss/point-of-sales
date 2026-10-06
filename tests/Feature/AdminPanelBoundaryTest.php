<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Filament\Pages\Production;
use App\Filament\Pages\StockOpname;
use App\Filament\Widgets\PlatformOverviewWidget;
use App\Models\CashierShift;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Store;
use App\Filament\Resources\Products\ProductResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_admin_can_access_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);

        $this->actingAs($admin)->get('/admin')
            ->assertOk();
    }

    public function test_non_admin_roles_cannot_access_admin_panel(): void
    {
        foreach ([UserRole::Owner, UserRole::Manager, UserRole::Cashier] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);

            $this->actingAs($user)->get('/admin')->assertForbidden();
        }
    }

    public function test_guest_is_redirected_to_central_login(): void
    {
        $this->get('/admin')->assertRedirect(route('auth.login'));
    }

    public function test_inactive_admin_cannot_access_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => false]);

        $this->actingAs($admin)->get('/admin')->assertForbidden();
    }

    public function test_admin_panel_contract_remains_admin_only(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue(User::factory()->make(['role' => UserRole::Admin, 'is_active' => true])->canAccessPanel($panel));
        $this->assertFalse(User::factory()->make(['role' => UserRole::Owner, 'is_active' => true])->canAccessPanel($panel));
        $this->assertFalse(User::factory()->make(['role' => UserRole::Manager, 'is_active' => true])->canAccessPanel($panel));
        $this->assertFalse(User::factory()->make(['role' => UserRole::Cashier, 'is_active' => true])->canAccessPanel($panel));
    }

    public function test_platform_overview_reports_real_platform_metrics(): void
    {
        $baseline = [
            Store::query()->where('status', StoreStatus::Active)->count(),
            User::query()->where('is_active', true)->count(),
            CashierShift::query()->where('status', ShiftStatus::Open)->count(),
            Payment::query()->where('status', PaymentStatus::Pending)->count(),
        ];
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $activeUser = User::factory()->create(['role' => UserRole::Cashier, 'is_active' => true]);
        User::factory()->create(['role' => UserRole::Manager, 'is_active' => false]);
        $store = Store::create(['name' => 'Platform Store', 'status' => StoreStatus::Active]);

        $shift = CashierShift::create([
            'store_id' => $store->id,
            'user_id' => $activeUser->id,
            'status' => ShiftStatus::Open,
            'opened_at' => now(),
            'opening_cash' => 0,
        ]);

        $sale = Sale::create([
            'store_id' => $store->id,
            'invoice_number' => 'PLT-TEST-000001',
            'cashier_shift_id' => $shift->id,
            'cashier_id' => $activeUser->id,
            'status' => SaleStatus::PendingPayment,
            'subtotal' => 10000,
            'grand_total' => 10000,
        ]);

        Payment::create([
            'sale_id' => $sale->id,
            'method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Pending,
            'amount_due' => 10000,
            'amount_received' => 0,
            'change_amount' => 0,
            'paid_at' => now(),
            'received_by' => $activeUser->id,
        ]);

        $this->actingAs($admin);
        $widget = app(PlatformOverviewWidget::class);
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($widget);

        $this->assertSame([
            (string) ($baseline[0] + 1),
            (string) ($baseline[1] + 2),
            (string) ($baseline[2] + 1),
            (string) ($baseline[3] + 1),
        ], array_map(
            static fn ($stat): mixed => $stat->getValue(),
            $stats,
        ));
    }

    public function test_operational_legacy_surfaces_are_not_admin_navigation_entries(): void
    {
        $this->assertFalse(ProductResource::canViewAny());
        $this->assertFalse(Production::canAccess());
        $this->assertFalse(StockOpname::canAccess());
    }

    public function test_hidden_legacy_resource_cannot_be_opened_directly(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);

        $this->actingAs($admin)->get('/admin/products')->assertForbidden();
    }
}
