<?php

namespace App\Livewire\Cashier;

use App\Livewire\Pos\Checkout as PosCheckoutComponent;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.cashier', ['title' => 'POS Kasir'])]
class PosCheckout extends PosCheckoutComponent
{
    public function render()
    {
        return view('livewire.cashier.pos-checkout', [
            'title' => 'POS Kasir',
        ]);
    }
}
