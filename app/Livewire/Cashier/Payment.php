<?php

namespace App\Livewire\Cashier;

use App\Actions\Sale\AddPaymentAction;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Payment extends Component
{
    public ?Sale $sale = null;

    public string $method = 'cash';

    public string $amountReceived = '0';

    public string $amountToPay = '0';

    public string $referenceNumber = '';

    public string $memberSearch = '';

    public string $memberCardCode = '';

    public ?string $message = null;

    public function mount(): void
    {
        $this->sale = Sale::query()
            ->with(['items', 'customer', 'payments'])
            ->whereKey((int) request()->integer('sale'))
            ->where('cashier_id', $this->currentUser()->id)
            ->where('store_id', $this->currentUser()->store_id)
            ->where('status', SaleStatus::PendingPayment)
            ->first();

        if (! $this->sale) {
            $this->redirect('/cashier/pos');

            return;
        }

        $this->amountReceived = (string) $this->sale->grand_total;
        $this->amountToPay = (string) $this->sale->grand_total;
    }

    public function updatedMethod(): void
    {
        if ($this->sale && $this->method !== PaymentMethod::Cash->value) {
            $this->amountToPay = (string) $this->remainingDue();
            $this->amountReceived = $this->amountToPay;
        }
    }

    public function selectMember(int $customerId): void
    {
        if (! $this->sale) return;

        $customer = Customer::query()->where('store_id', $this->currentUser()->store_id)->where('is_active', true)->find($customerId);

        if (! $customer) {
            $this->addError('member', 'Member tidak ditemukan atau tidak aktif.');

            return;
        }

        $this->sale->update(['customer_id' => $customer->id]);
        $this->sale->setRelation('customer', $customer);
        $this->memberSearch = '';
        $this->memberCardCode = '';
        $this->message = 'Member '.$customer->name.' dipilih.';
    }

    public function scanMemberCard(): void
    {
        $code = trim($this->memberCardCode);

        if ($code === '') {
            $this->addError('member', 'Masukkan atau scan kode kartu member terlebih dahulu.');

            return;
        }

        $customer = Customer::query()->where('store_id', $this->currentUser()->store_id)->where('is_active', true)->where(fn ($query) => $query->where('code', $code)->orWhere('card_number', $code))->first();

        if (! $customer) {
            $this->addError('member', 'Kartu member tidak dikenali.');

            return;
        }

        $this->selectMember($customer->id);
    }

    public function clearMember(): void
    {
        if (! $this->sale) return;
        $this->sale->update(['customer_id' => null]);
        $this->sale->unsetRelation('customer');
        $this->memberSearch = '';
        $this->memberCardCode = '';
    }

    public function processPayment(): void
    {
        if (! $this->sale) {
            $this->addError('payment', 'Transaksi tidak ditemukan.');

            return;
        }

        $data = $this->validate([
            'method' => ['required', 'in:cash,card,bank_transfer,qris,other'],
            'amountToPay' => ['required', 'integer', 'min:1'],
            'amountReceived' => ['required', 'integer', 'min:0'],
            'referenceNumber' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $sale = app(AddPaymentAction::class)->handle($this->sale, $this->currentUser(), PaymentMethod::from($data['method']), (int) $data['amountToPay'], (int) $data['amountReceived'], $data['referenceNumber'] ?: null);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) foreach ($messages as $message) $this->addError($field, $message);

            return;
        }

        if ($sale->status === SaleStatus::Paid) {
            $this->redirect('/cashier/receipt-page?sale='.$sale->id);

            return;
        }

        $this->sale = $sale->load(['items', 'customer', 'payments']);
        $this->amountToPay = (string) $this->remainingDue();
        $this->amountReceived = $this->amountToPay;
        $this->referenceNumber = '';
        $this->message = 'Pembayaran dicatat. Sisa tagihan Rp '.number_format($this->remainingDue(), 0, ',', '.').'.';
    }

    public function remainingDue(?Sale $sale = null): int
    {
        $sale ??= $this->sale;
        if (! $sale) return 0;
        $paid = $sale->relationLoaded('payments') ? $sale->payments->where('status', PaymentStatus::Completed)->sum('amount_due') : $sale->payments()->where('status', PaymentStatus::Completed)->sum('amount_due');

        return max(0, $sale->grand_total - (int) $paid);
    }

    public function getMemberMatchesProperty()
    {
        $keyword = trim($this->memberSearch);
        if (mb_strlen($keyword) < 2) return collect();

        return Customer::query()->where('store_id', $this->currentUser()->store_id)->where('is_active', true)->where(fn ($query) => $query->where('name', 'like', "%{$keyword}%")->orWhere('phone', 'like', "%{$keyword}%")->orWhere('code', 'like', "%{$keyword}%")->orWhere('card_number', 'like', "%{$keyword}%"))->orderBy('name')->limit(6)->get();
    }

    public function render()
    {
        return view('livewire.cashier.payment')->layout('components.layouts.cashier', ['title' => 'Pembayaran']);
    }

    private function currentUser(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === \App\Enums\UserRole::Cashier && $user->is_active, 403);

        return $user;
    }
}
