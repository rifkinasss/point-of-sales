<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\StoreSubscription;
use App\Models\StoreInvoice;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class SubscriptionManager extends Page
{
    protected string $view = 'filament.pages.subscription-manager';

    protected static ?string $navigationLabel = 'Langganan toko';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 20;

    /** @return Collection<int, StoreSubscription> */
    public function getSubscriptionsProperty(): Collection
    {
        return StoreSubscription::withoutGlobalScopes()->with(['store', 'invoices'])->latest()->get();
    }

    public function activate(int $subscriptionId): void
    {
        $subscription = StoreSubscription::withoutGlobalScopes()->with('store')->findOrFail($subscriptionId);
        $definition = config("pos-plans.{$subscription->plan}");

        if (! is_array($definition) || ! $subscription->store) {
            return;
        }

        if ($subscription->status !== 'pending') return;
        $subscription->update(['status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth()]);
        StoreInvoice::withoutGlobalScopes()->where('store_subscription_id', $subscription->id)->where('status', 'pending')->update(['status' => 'paid', 'paid_at' => now()]);
        $subscription->store->update(['plan' => $subscription->plan, 'product_limit' => $definition['product_limit'], 'staff_limit' => $definition['staff_limit']]);
        Notification::make()->success()->title('Paket toko telah diaktifkan.')->send();
    }

    public static function canAccess(): bool
    {
        return Filament::auth()->user() instanceof User && Filament::auth()->user()->role === UserRole::Admin;
    }
}
