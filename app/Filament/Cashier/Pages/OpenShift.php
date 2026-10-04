<?php

namespace App\Filament\Cashier\Pages;

use App\Actions\Shift\OpenShiftAction;
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

class OpenShift extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.cashier.pages.open-shift';

    protected static ?string $navigationLabel = 'Buka shift';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockOpen;

    protected static ?string $title = 'Buka Shift Kasir';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        if (session()->has('cashier.shift_closed_at')) {
            return;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('opening_cash')
                    ->label('Kas awal')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->autofocus()
                    ->required(),
                Textarea::make('opening_note')
                    ->label('Catatan pembukaan')
                    ->maxLength(1000),
            ])
            ->statePath('data');
    }

    public function openShift(): void
    {
        if (session()->has('cashier.shift_closed_at')) {
            Notification::make()
                ->danger()
                ->title('Shift sebelumnya sudah ditutup.')
                ->body('Keluar dari akun terlebih dahulu sebelum memulai shift baru.')
                ->send();

            return;
        }

        $data = $this->form->getState();

        app(OpenShiftAction::class)->handle(
            $this->currentUser(),
            (int) $data['opening_cash'],
            $data['opening_note'] ?? null,
        );

        Notification::make()
            ->success()
            ->title('Shift berhasil dibuka')
            ->send();

        $this->redirect('/cashier/pos-checkout');
    }

    private function currentUser(): User
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException;
        }

        return $user;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && ! CashierShift::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->exists();
    }
}
