<?php

namespace App\Livewire\Manager\Transactions;

use App\Actions\Sale\RefundSaleAction;
use App\Actions\Sale\VoidSaleAction;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Sale;
use App\Models\User;
use App\Queries\ManagerTransactionQuery;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Show extends Component
{
    public ?string $salePublicId = null;
    public bool $actionModalOpen = false;
    public string $actionType = '';
    public string $reason = '';

    public function mount(string|Sale $sale): void
    {
        $this->authorizeManager();
        $this->salePublicId = $sale instanceof Sale ? $sale->public_id : $sale;
        abort_unless($this->sale !== null, 404);
    }

    public function openAction(string $type): void
    {
        $sale = $this->sale;
        abort_unless($sale instanceof Sale, 404);

        if ($type === 'refund') {
            abort_unless($sale->status === SaleStatus::Paid, 422);
        } elseif ($type === 'void') {
            abort_unless($sale->status === SaleStatus::PendingPayment, 422);
        } else {
            abort(404);
        }

        $this->resetValidation();
        $this->actionType = $type;
        $this->reason = '';
        $this->actionModalOpen = true;
    }

    public function closeAction(): void
    {
        $this->actionModalOpen = false;
        $this->resetValidation();
        $this->reason = '';
    }

    public function confirmAction(): void
    {
        $manager = $this->authorizeManager();
        $this->validate(['reason' => ['required', 'string', 'max:1000']]);
        $sale = app(ManagerTransactionQuery::class)->find($manager, (string) $this->salePublicId);
        abort_unless($sale instanceof Sale, 404);

        try {
            if ($this->actionType === 'refund') {
                app(RefundSaleAction::class)->handle($sale, $manager, $this->reason);
            } elseif ($this->actionType === 'void') {
                app(VoidSaleAction::class)->handle($sale, $manager, $this->reason);
            } else {
                throw ValidationException::withMessages(['actionType' => 'Aksi transaksi tidak dikenali.']);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        }

        session()->flash('status', $this->actionType === 'refund' ? 'Refund transaksi berhasil diproses.' : 'Transaksi berhasil di-void.');
        $this->redirectRoute('manager.transactions.show', ['sale' => $this->salePublicId]);
    }

    public function render()
    {
        $this->authorizeManager();

        return view('livewire.manager.transactions.show', [
            'sale' => $this->sale,
        ])->layout('components.layouts.manager', ['title' => 'Detail Transaksi', 'activeNav' => 'transactions']);
    }

    public function getSaleProperty(): ?Sale
    {
        $manager = $this->authorizeManager();

        return $this->salePublicId ? app(ManagerTransactionQuery::class)->find($manager, $this->salePublicId) : null;
    }

    private function authorizeManager(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active && filled($user->store_id), 403);

        return $user;
    }
}
