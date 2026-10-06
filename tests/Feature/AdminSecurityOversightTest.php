<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\AuditLogger;
use App\Filament\Resources\Security\UserSessionResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminSecurityOversightTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_audit_logs_and_active_sessions_but_other_roles_cannot(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);

        $this->actingAs($admin);
        $this->assertTrue(UserSessionResource::canViewAny());
        $this->actingAs($admin)->get('/admin/audit-logs')->assertOk();
        $this->actingAs($admin)->get('/admin/security/user-sessions')->assertOk();

        $this->actingAs($owner)->get('/admin/audit-logs')->assertForbidden();
        $this->actingAs($owner)->get('/admin/security/user-sessions')->assertForbidden();
        Auth::logout();
        $this->get('/admin/audit-logs')->assertRedirect(route('auth.login'));
    }

    public function test_audit_logs_are_read_only_and_show_platform_reference_without_numeric_id_as_primary_reference(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $store = Store::query()->create(['name' => 'Audit Store', 'store_code' => 'AUD']);
        $audit = AuditLog::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'user_id' => $admin->id,
            'event' => 'store_status_changed',
            'auditable_type' => Store::class,
            'auditable_id' => $store->id,
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'inactive'],
        ]);

        $this->actingAs($admin)->get('/admin/audit-logs')
            ->assertOk()
            ->assertSee('AUD')
            ->assertDontSee('Resource reference');
        $this->actingAs($admin)->get('/admin/audit-logs/'.$audit->id)->assertOk()->assertSee('AUD');
        $this->actingAs($admin)->get('/admin/audit-logs/create')->assertNotFound();
        $this->actingAs($admin)->get('/admin/audit-logs/'.$audit->id.'/edit')->assertNotFound();
    }

    public function test_audit_logger_redacts_authentication_secrets(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $store = Store::query()->create(['name' => 'Secure Store', 'store_code' => 'SEC']);

        $this->actingAs($admin);
        app(AuditLogger::class)->log('security_test', $store, [
            'password' => 'old-secret',
            'status' => 'active',
        ], [
            'access_token' => 'token-value',
            'status' => 'inactive',
        ]);

        $audit = AuditLog::withoutGlobalScopes()->latest('id')->firstOrFail();
        $this->assertSame('[REDACTED]', $audit->old_values['password']);
        $this->assertSame('[REDACTED]', $audit->new_values['access_token']);
        $this->assertSame('inactive', $audit->new_values['status']);
    }

    public function test_login_success_failure_and_logout_are_traceable_without_password_data(): void
    {
        $user = User::factory()->create([
            'email' => 'audit-login@example.com',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->from('/auth/login')->post('/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'login_failed', 'auditable_id' => $user->id]);

        $this->post('/auth/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'login_succeeded', 'auditable_id' => $user->id]);

        $this->post('/auth/logout');
        $this->assertDatabaseHas('audit_logs', ['event' => 'logout_completed', 'auditable_id' => $user->id]);
        $this->assertDatabaseMissing('audit_logs', ['new_values->password' => 'password']);
    }
}
