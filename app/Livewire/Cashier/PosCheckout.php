<?php

namespace App\Livewire\Cashier;

use App\Actions\Sale\AddPaymentAction;
use App\Actions\Sale\CompleteSalePaymentAction;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Livewire\Pos\Checkout as PosCheckoutComponent;
use App\Models\Sale;
use App\Models\User;
use App\Services\PaymentAllocator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.cashier', ['title' => 'POS Kasir'])]
class PosCheckout extends PosCheckoutComponent
{
    /** @var list<string> */
    private const MANUAL_PAYMENT_METHODS = ['cash', 'qris', 'bank_transfer'];

    public bool $paymentModalOpen = false;

    public ?Sale $paymentSale = null;

    public string $paymentMethod = 'cash';

    public int $paymentAmount = 0;

    public int $cashReceived = 0;

    public string $paymentReference = '';

    public bool $paymentSuccess = false;

    public ?int $paymentChange = null;

    public ?string $paymentNotice = null;

    public function mount(): void
    {
        parent::mount();

        if (request()->filled('sale')) {
            $this->openPaymentForSale((int) request()->integer('sale'));
        }
    }

    public function createPendingSale(): void
    {
        if ($this->paymentSale?->status === SaleStatus::PendingPayment) {
            $this->openPaymentForSale($this->paymentSale->id);

            return;
        }

        $this->openPaymentForSale($this->createPendingSaleRecord()->id);
    }

    public function selectPaymentMethod(string $method): void
    {
        $paymentMethod = PaymentMethod::tryFrom($method);

        if (! $paymentMethod || ! in_array($paymentMethod->value, self::MANUAL_PAYMENT_METHODS, true)) {
            return;
        }

        $this->paymentMethod = $paymentMethod->value;
        $this->paymentAmount = $this->paymentRemainingDue;
        $this->cashReceived = $this->paymentAmount;
        $this->paymentReference = '';
        $this->resetValidation('payment');
    }

    public function addPayment(): void
    {
        if (! $this->paymentSale) {
            $this->addError('payment', 'Transaksi pembayaran tidak ditemukan.');

            return;
        }

        $data = $this->validate([
            'paymentMethod' => ['required', 'in:cash,bank_transfer,qris'],
            'paymentAmount' => ['required', 'integer', 'min:1'],
            'cashReceived' => ['required', 'integer', 'min:0'],
            'paymentReference' => ['nullable', 'string', 'max:100'],
        ]);

        $method = PaymentMethod::from($data['paymentMethod']);
        $received = $method->isCash() ? (int) $data['cashReceived'] : (int) $data['paymentAmount'];

        try {
            $sale = app(AddPaymentAction::class)->handle(
                $this->paymentSale,
                $this->cashierUser(),
                $method,
                (int) $data['paymentAmount'],
                $received,
                filled($data['paymentReference']) ? $data['paymentReference'] : null,
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field === 'amount' ? 'paymentAmount' : ($field === 'amount_received' ? 'cashReceived' : $field), $message);
                }
            }

            return;
        }

        $this->paymentSale = $sale->load(['items', 'customer', 'payments']);
        $this->paymentChange = $this->paymentSale->payments->last()?->change_amount;
        $this->paymentReference = '';

        if ($this->paymentSale->status === SaleStatus::Paid) {
            $this->paymentSuccess = true;
            $this->paymentAmount = 0;
            $this->cashReceived = 0;
            $this->paymentNotice = 'Pembayaran berhasil diselesaikan.';

            return;
        }

        $this->paymentAmount = $this->paymentRemainingDue;
        $this->cashReceived = $this->paymentAmount;
        $this->paymentNotice = 'Pembayaran ditambahkan. Sisa Rp '.number_format($this->paymentRemainingDue, 0, ',', '.').'.';
    }

    public function completePayment(): void
    {
        if (! $this->paymentSale || $this->paymentRemainingDue !== 0) {
            $this->addError('payment', 'Selesaikan alokasi pembayaran terlebih dahulu.');

            return;
        }

        try {
            $this->paymentSale = app(CompleteSalePaymentAction::class)->handle($this->paymentSale, $this->cashierUser());
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        $this->paymentSuccess = true;
        $this->paymentNotice = 'Pembayaran berhasil diselesaikan.';
    }

    public function closePayment(): void
    {
        if ($this->paymentSummary?->paymentCount > 0 && ! $this->paymentSuccess) {
            $this->addError('payment', 'Pembayaran sudah ditambahkan. Selesaikan transaksi atau lanjutkan alokasi.');

            return;
        }

        $this->paymentModalOpen = false;
        $this->paymentNotice = null;
    }

    public function startNewTransaction(): void
    {
        if (! $this->paymentSuccess) {
            return;
        }

        $this->clearCart();
        $this->clearCustomer();
        $this->paymentSale = null;
        $this->paymentModalOpen = false;
        $this->paymentSuccess = false;
        $this->paymentChange = null;
        $this->paymentNotice = null;
        $this->paymentAmount = 0;
        $this->cashReceived = 0;
    }

    public function getPaymentSummaryProperty(): mixed
    {
        if (! $this->paymentSale) {
            return null;
        }

        return app(PaymentAllocator::class)->summarize($this->paymentSale);
    }

    public function getPaymentRemainingDueProperty(): int
    {
        return (int) ($this->paymentSummary?->remainingDue ?? 0);
    }

    public function getPaymentChangePreviewProperty(): int
    {
        if ($this->paymentMethod !== PaymentMethod::Cash->value) {
            return 0;
        }

        return max(0, $this->cashReceived - $this->paymentAmount);
    }

    public function getManualQrisImageProperty(): ?string
    {
        return config('payment.manual_qris.image_url');
    }

    /** @return array{bank_name: ?string, account_number: ?string, account_holder: ?string} */
    public function getManualTransferDetailsProperty(): array
    {
        return [
            'bank_name' => config('payment.manual_transfer.bank_name'),
            'account_number' => config('payment.manual_transfer.account_number'),
            'account_holder' => config('payment.manual_transfer.account_holder'),
        ];
    }

    private function openPaymentForSale(int $saleId): void
    {
        $sale = Sale::query()
            ->with(['items', 'customer', 'payments'])
            ->whereKey($saleId)
            ->where('cashier_id', $this->cashierUser()->id)
            ->where('store_id', $this->cashierUser()->store_id)
            ->where('status', SaleStatus::PendingPayment)
            ->first();

        if (! $sale) {
            $this->redirect('/cashier/pos');

            return;
        }

        $this->paymentSale = $sale;
        $this->paymentModalOpen = true;
        $this->paymentSuccess = false;
        $this->paymentMethod = PaymentMethod::Cash->value;
        $this->paymentAmount = $this->paymentRemainingDue;
        $this->cashReceived = $this->paymentAmount;
        $this->paymentReference = '';
        $this->paymentChange = null;
        $this->paymentNotice = null;
    }

    private function cashierUser(): User
    {
        $user = User::query()->find(auth()->id());
        abort_unless($user instanceof User && $user->role === \App\Enums\UserRole::Cashier && $user->is_active, 403);

        return $user;
    }

    public function render()
    {
        return view('livewire.cashier.pos-checkout', [
            'title' => 'POS Kasir',
        ]);
    }
}
