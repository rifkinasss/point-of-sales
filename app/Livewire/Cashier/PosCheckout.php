<?php

namespace App\Livewire\Cashier;

use App\Livewire\Pos\Checkout as PosCheckoutComponent;
use Illuminate\Contracts\View\View;

class PosCheckout extends PosCheckoutComponent
{
    public function render(): View
    {
        return view('livewire.cashier.pos-checkout')
            ->layout('components.layouts.cashier', [
                'title' => 'POS Kasir',
            ]);
    }
}
