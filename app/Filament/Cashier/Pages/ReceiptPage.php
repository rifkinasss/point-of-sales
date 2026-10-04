<?php

namespace App\Filament\Cashier\Pages;

use App\Enums\SaleStatus;
use App\Mail\ReceiptMail;
use App\Models\Sale;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Mail;

class ReceiptPage extends Page
{
    protected string $view = 'filament.cashier.pages.receipt-page';

    protected static ?string $title = 'Struk';

    public ?Sale $sale = null;

    public string $receiptEmail = '';

    public function mount(): void
    {
        $saleId = (int) request()->integer('sale');
        $this->sale = Sale::query()
            ->with(['items', 'payment', 'cashier', 'customer', 'store'])
            ->whereKey($saleId)
            ->where('cashier_id', $this->currentUser()->id)
            ->where('status', SaleStatus::Paid)
            ->first();

        if (! $this->sale) {
            Notification::make()->warning()->title('Struk transaksi tidak ditemukan.')->send();
            $this->redirect('/cashier/pos-checkout');
        }

        $this->receiptEmail = $this->sale?->customer?->email ?? '';
    }

    public function emailReceipt(): void
    {
        $this->validate(['receiptEmail' => ['required', 'email']]);

        if (! $this->sale) {
            return;
        }

        Mail::to($this->receiptEmail)->send(new ReceiptMail($this->sale));
        Notification::make()->success()->title('Struk telah dikirim ke email pelanggan.')->send();
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
