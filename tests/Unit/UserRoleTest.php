<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_admin_can_assign_owner_manager_and_cashier(): void
    {
        $options = UserRole::assignableOptionsFor(UserRole::Admin);

        $this->assertArrayHasKey(UserRole::Owner->value, $options);
        $this->assertArrayHasKey(UserRole::Manager->value, $options);
        $this->assertArrayHasKey(UserRole::Cashier->value, $options);
    }

    public function test_owner_can_only_assign_manager_and_cashier(): void
    {
        $options = UserRole::assignableOptionsFor(UserRole::Owner);

        $this->assertArrayNotHasKey(UserRole::Admin->value, $options);
        $this->assertArrayNotHasKey(UserRole::Owner->value, $options);
        $this->assertArrayHasKey(UserRole::Manager->value, $options);
        $this->assertArrayHasKey(UserRole::Cashier->value, $options);
    }
}
