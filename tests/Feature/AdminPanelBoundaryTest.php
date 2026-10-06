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
use App\Filament\Widgets\PlatformDistributionWidget;
use App\Filament\Widgets\PlatformOverviewWidget;
use App\Filament\Widgets\RecentAdminActivityWidget;
use App\Models\AuditLog;
use App\Models\CashierShift;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Store;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\Stores\StoreResource;
use App\Filament\Resources\Users\UserResource;
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
            '0',
            '1',
            (string) ($baseline[2] + 1),
            (string) ($baseline[3] + 1),
        ], array_map(
            static fn ($stat): mixed => $stat->getValue(),
            $stats,
        ));
    }

    public function test_admin_dashboard_supporting_widgets_use_platform_data_and_admin_boundary(): void
    {
        $activeStoreBaseline = Store::query()->where('status', StoreStatus::Active)->count();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);
        $store = Store::create(['name' => 'Activity Store', 'status' => StoreStatus::Active]);

        AuditLog::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'user_id' => $admin->id,
            'event' => 'store_status_changed',
            'auditable_type' => Store::class,
            'auditable_id' => $store->id,
            'old_values' => ['status' => StoreStatus::Active->value],
            'new_values' => ['status' => StoreStatus::Inactive->value],
        ]);

        $this->actingAs($admin);

        $distributionWidget = app(PlatformDistributionWidget::class);
        $distributionMethod = new \ReflectionMethod($distributionWidget, 'getViewData');
        $distribution = $distributionMethod->invoke($distributionWidget);
        $this->assertSame(1, $distribution['roles']['Admin']);
        $this->assertSame(1, $distribution['roles']['Owner']);
        $this->assertSame($activeStoreBaseline + 1, $distribution['stores']['Aktif']);

        $activityWidget = app(RecentAdminActivityWidget::class);
        $activityMethod = new \ReflectionMethod($activityWidget, 'getTableQuery');
        $this->assertSame(1, $activityMethod->invoke($activityWidget)->count());
        $this->assertTrue(PlatformDistributionWidget::canView());

        $this->actingAs($owner);
        $this->assertFalse(PlatformDistributionWidget::canView());
        $this->assertFalse(RecentAdminActivityWidget::canView());
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

    public function test_admin_detail_pages_present_identity_state_and_context(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $user = User::factory()->create(['name' => 'Detail User', 'role' => UserRole::Manager, 'is_active' => true]);
        $store = Store::create(['name' => 'Detail Store', 'store_code' => 'DET', 'status' => StoreStatus::Active]);

        AuditLog::withoutGlobalScopes()->create([
            'user_id' => $admin->id,
            'store_id' => $store->id,
            'event' => 'store_status_changed',
            'auditable_type' => Store::class,
            'auditable_id' => $store->id,
            'old_values' => ['status' => StoreStatus::Inactive->value],
            'new_values' => ['status' => StoreStatus::Active->value],
        ]);

        $this->actingAs($admin);

        $this->get(UserResource::getUrl('view', ['record' => $user]))->assertOk();
        $this->get(StoreResource::getUrl('view', ['record' => $store]))->assertOk();
        $this->get(AuditLogResource::getUrl('view', ['record' => AuditLog::withoutGlobalScopes()->latest('id')->firstOrFail()]))->assertOk();
    }
}
