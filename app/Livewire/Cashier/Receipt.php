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
    public ?Sale $sale = null;

    public string $receiptEmail = '';

    public ?string $message = null;

    public function mount(): void
    {
        $this->sale = Sale::query()->with(['items', 'payments', 'cashier', 'customer', 'store'])->whereKey((int) request()->integer('sale'))->where('cashier_id', $this->currentUser()->id)->where('store_id', $this->currentUser()->store_id)->where('status', SaleStatus::Paid)->first();

        if (! $this->sale) {
            $this->redirect('/cashier/pos');

            return;
        }

        $this->receiptEmail = $this->sale->customer?->email ?? '';
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
        return view('livewire.cashier.receipt')->layout('components.layouts.cashier', ['title' => 'Struk']);
    }

    private function currentUser(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === \App\Enums\UserRole::Cashier && $user->is_active, 403);

        return $user;
    }
}
