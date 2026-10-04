<?php

namespace App\Filament\Cashier\Pages;

use App\Actions\Shift\CloseShiftAction;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;

class CloseShift extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.cashier.pages.close-shift';

    protected static ?string $navigationLabel = 'Tutup shift';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static ?string $title = 'Tutup Shift Kasir';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?CashierShift $shift = null;

    public function mount(): void
    {
        $this->shift = $this->currentShift();

        if (session()->has('shift_guard')) {
            Notification::make()->warning()->title((string) session('shift_guard'))->send();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('closing_cash_actual')
                    ->label('Kas fisik')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->required(),
                Textarea::make('closing_note')
                    ->label('Catatan penutupan')
                    ->helperText('Wajib diisi bila ada selisih kas.')
                    ->maxLength(1000),
            ])
            ->statePath('data');
    }

    public function closeShift(): void
    {
        if (! $this->shift) {
            Notification::make()->danger()->title('Tidak ada shift aktif untuk ditutup.')->send();

            return;
        }

        $data = $this->form->getState();

        app(CloseShiftAction::class)->handle(
            $this->shift,
            $this->currentUser(),
            (int) $data['closing_cash_actual'],
            $data['closing_note'] ?? null,
        );

        Notification::make()
            ->success()
            ->title('Shift berhasil ditutup dan kas fisik tercatat')
            ->send();

        session()->put('cashier.shift_closed_at', now()->toIso8601String());

        $this->redirect('/cashier/open-shift');
    }

    private function currentUser(): User
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException;
        }

        return $user;
    }

    private function currentShift(): ?CashierShift
    {
        return CashierShift::query()
            ->where('user_id', $this->currentUser()->id)
            ->where('status', ShiftStatus::Open)
            ->latest('opened_at')
            ->first();
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
