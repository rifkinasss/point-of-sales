<?php

namespace App\Livewire\Cashier;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\User;
use App\Mail\ReceiptMail;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class Receipt extends Component
{
    public ?string $saleInvoiceNumber = null;

    public string $receiptEmail = '';

    public ?string $message = null;

    public function mount(): void
    {
        $sale = Sale::query()->where('invoice_number', request()->string('sale')->toString())->where('cashier_id', $this->currentUser()->id)->where('store_id', $this->currentUser()->store_id)->where('status', SaleStatus::Paid)->first();

        if (! $sale) {
            $this->redirect('/cashier/pos');

            return;
        }

        $this->saleInvoiceNumber = $sale->invoice_number;
        $this->receiptEmail = $sale->customer?->email ?? '';
    }

    public function emailReceipt(): void
    {
        $this->validate(['receiptEmail' => ['required', 'email']]);
        if (! $this->sale) return;
        Mail::to($this->receiptEmail)->send(new ReceiptMail($this->sale));
        $this->message = 'Struk telah dikirim ke email pelanggan.';
    }

    public function render()
    {
        return view('livewire.cashier.receipt', ['sale' => $this->sale])->layout('components.layouts.cashier', ['title' => 'Struk']);
    }

    public function getSaleProperty(): ?Sale
    {
        return $this->saleInvoiceNumber
            ? Sale::query()->with(['items', 'payments', 'cashier', 'customer', 'store'])->where('invoice_number', $this->saleInvoiceNumber)->where('cashier_id', $this->currentUser()->id)->where('store_id', $this->currentUser()->store_id)->where('status', SaleStatus::Paid)->first()
            : null;
    }

    private function currentUser(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === \App\Enums\UserRole::Cashier && $user->is_active, 403);

        return $user;
    }
}
