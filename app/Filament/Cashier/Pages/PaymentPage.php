<?php

namespace App\Filament\Cashier\Pages;

use App\Actions\Sale\ProcessPaymentAction;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Collection;

class PaymentPage extends Page
{
    protected string $view = 'filament.cashier.pages.payment-page';

    protected static ?string $title = 'Pembayaran';

    public ?Sale $sale = null;

    public string $method = 'cash';

    public int $amountReceived = 0;

    public ?string $referenceNumber = null;

    /** Pencarian nama, telepon, atau kode member tanpa memuat seluruh member. */
    public string $memberSearch = '';

    /** Nilai dari barcode / QR pada kartu member. */
    public string $memberCardCode = '';

    public function mount(): void
    {
        $saleId = (int) request()->integer('sale');
        $this->sale = Sale::query()
            ->with(['items', 'customer'])
            ->whereKey($saleId)
            ->where('cashier_id', $this->currentUser()->id)
            ->where('status', SaleStatus::PendingPayment)
            ->first();

        if (! $this->sale) {
            Notification::make()->warning()->title('Transaksi pending tidak ditemukan.')->send();
            $this->redirect('/cashier/pos-checkout');

            return;
        }

        $this->amountReceived = $this->sale->grand_total;
    }

    public function updatedMethod(): void
    {
        if ($this->sale && $this->method !== PaymentMethod::Cash->value) {
            $this->amountReceived = $this->sale->grand_total;
        }
    }

    /** @return Collection<int, Customer> */
    public function getMemberMatchesProperty(): Collection
    {
        $keyword = trim($this->memberSearch);

        if (mb_strlen($keyword) < 2) {
            return new Collection;
        }

        return Customer::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->where('name', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%")
                ->orWhere('code', 'like', "%{$keyword}%")
                ->orWhere('card_number', 'like', "%{$keyword}%"))
            ->orderBy('name')
            ->limit(6)
            ->get();
    }

    public function selectMember(int $customerId): void
    {
        if (! $this->sale) {
            return;
        }

        $customer = Customer::query()->where('is_active', true)->find($customerId);

        if (! $customer) {
            Notification::make()->danger()->title('Member tidak ditemukan atau tidak aktif.')->send();

            return;
        }

        $this->sale->update(['customer_id' => $customer->id]);
        $this->sale->setRelation('customer', $customer);
        $this->memberSearch = '';
        $this->memberCardCode = '';
        Notification::make()->success()->title('Member '.$customer->name.' dipilih.')->send();
    }

    public function scanMemberCard(): void
    {
        $code = trim($this->memberCardCode);

        if ($code === '') {
            return;
        }

        $customer = Customer::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('code', $code)->orWhere('card_number', $code))
            ->first();

        if (! $customer) {
            Notification::make()->warning()->title('Kartu member tidak dikenali.')->body('Pastikan kode kartu sudah benar atau cari member secara manual.')->send();

            return;
        }

        $this->selectMember($customer->id);
    }

    public function clearMember(): void
    {
        if (! $this->sale) {
            return;
        }

        $this->sale->update(['customer_id' => null]);
        $this->sale->unsetRelation('customer');
        $this->memberSearch = '';
        $this->memberCardCode = '';
    }

    public function processPayment(): void
    {
        if (! $this->sale) {
            throw ValidationException::withMessages(['sale' => 'Transaksi tidak ditemukan.']);
        }

        $sale = app(ProcessPaymentAction::class)->handle(
            $this->sale,
            $this->currentUser(),
            PaymentMethod::from($this->method),
            $this->amountReceived,
            $this->referenceNumber,
        );

        Notification::make()
            ->success()
            ->title('Pembayaran berhasil')
            ->body("Invoice {$sale->invoice_number} telah lunas.")
            ->send();

        $this->redirect("/cashier/receipt-page?sale={$sale->id}");
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calculator')
                ->label('Kalkulator')
                ->icon(Heroicon::OutlinedCalculator)
                ->modalHeading('Kalkulator pembayaran')
                ->modalDescription('Hasil perhitungan akan langsung digunakan sebagai uang diterima.')
                ->schema([
                    TextInput::make('first_number')
                        ->label('Angka pertama')
                        ->numeric()
                        ->required(),
                    Select::make('operator')
                        ->label('Operasi')
                        ->options([
                            'add' => 'Tambah (+)',
                            'subtract' => 'Kurang (−)',
                            'multiply' => 'Kali (×)',
                            'divide' => 'Bagi (÷)',
                        ])
                        ->default('add')
                        ->required(),
                    TextInput::make('second_number')
                        ->label('Angka kedua')
                        ->numeric()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $first = (float) $data['first_number'];
                    $second = (float) $data['second_number'];

                    if ($data['operator'] === 'divide' && $second === 0.0) {
                        Notification::make()
                            ->danger()
                            ->title('Tidak dapat membagi dengan nol.')
                            ->send();

                        return;
                    }

                    $result = match ($data['operator']) {
                        'subtract' => $first - $second,
                        'multiply' => $first * $second,
                        'divide' => $first / $second,
                        default => $first + $second,
                    };

                    $this->amountReceived = max(0, (int) round($result));

                    Notification::make()
                        ->success()
                        ->title('Hasil kalkulator diterapkan')
                        ->body('Uang diterima: Rp '.number_format($this->amountReceived, 0, ',', '.'))
                        ->send();
                }),
        ];
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
        return false;
    }
}
