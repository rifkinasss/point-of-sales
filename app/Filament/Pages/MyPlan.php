<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\StoreSubscription;
use App\Models\StoreInvoice;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MyPlan extends Page
{
    protected string $view = 'filament.pages.my-plan';

    protected static ?string $navigationLabel = 'Paket saya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 5;

    public function getStoreProperty()
    {
        return Filament::auth()->user()?->store;
    }

    public function getPendingSubscriptionProperty(): ?StoreSubscription
    {
        return $this->store?->subscriptions()->where('status', 'pending')->latest()->first();
    }

    public function getPendingInvoiceProperty(): ?StoreInvoice
    {
        return $this->store?->invoices()->where('status', 'pending')->latest()->first();
    }

    public function requestUpgrade(string $plan): void
    {
        $store = $this->store;
        $definition = config("pos-plans.{$plan}");

        if (! $store || ! is_array($definition) || $plan === 'free' || $this->pendingSubscription) {
            Notification::make()->warning()->title('Permintaan upgrade tidak dapat diproses saat ini.')->send();

            return;
        }

        $subscription = $store->subscriptions()->create([
            'plan' => $plan,
            'status' => 'pending',
            'amount' => $definition['price'],
            'requested_at' => now(),
        ]);

        $store->invoices()->create([
            'store_subscription_id' => $subscription->id,
            'invoice_number' => 'INV-'.now()->format('YmdHis'),
            'status' => 'pending',
            'amount' => $definition['price'],
            'due_at' => now()->addDays(3),
            'note' => 'Tagihan upgrade paket '.$definition['label'],
        ]);

        Notification::make()->success()->title('Tagihan dibuat dan permintaan upgrade dikirim ke Admin.')->send();
    }

    public static function canAccess(): bool
    {
        return Filament::auth()->user() instanceof User && Filament::auth()->user()->role === UserRole::Owner;
    }
}
