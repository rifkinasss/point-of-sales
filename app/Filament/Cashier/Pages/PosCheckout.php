<?php

namespace App\Filament\Cashier\Pages;

use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class PosCheckout extends Page
{
    protected string $view = 'filament.cashier.pages.pos-checkout';

    protected static ?string $navigationLabel = 'Kasir';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $title = null;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(): void
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User || ! CashierShift::query()
            ->where('user_id', $user->id)
            ->where('status', ShiftStatus::Open)
            ->exists()) {
            Notification::make()->warning()->title('Buka shift terlebih dahulu.')->send();
            $this->redirect('/cashier/open-shift');
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && CashierShift::query()
            ->where('user_id', $user->id)
            ->where('status', ShiftStatus::Open)
            ->exists();
    }
}
