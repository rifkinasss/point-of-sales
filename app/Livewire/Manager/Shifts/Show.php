<?php

namespace App\Livewire\Manager\Shifts;

use App\Enums\CashMovementType;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\Payment;
use App\Models\SaleReturn;
use App\Models\User;
use App\Queries\ManagerShiftQuery;
use App\Services\CashBalanceCalculator;
use Livewire\Component;

class Show extends Component
{
    public ?string $shiftPublicId = null;

    public function mount(string|CashierShift $shift): void
    {
        $this->authorizeManager();
        $this->shiftPublicId = $shift instanceof CashierShift ? $shift->public_id : $shift;
        abort_unless($this->shift instanceof CashierShift, 404);
    }

    public function render()
    {
        $this->authorizeManager();
        $shift = $this->shift;
        abort_unless($shift instanceof CashierShift, 404);

        return view('livewire.manager.shifts.show', [
            'shift' => $shift,
            'summary' => app(CashBalanceCalculator::class)->summary($shift),
            'movements' => $this->movements($shift),
        ])->layout('components.layouts.manager', ['title' => 'Detail Shift', 'activeNav' => 'shifts']);
    }

    public function getShiftProperty(): ?CashierShift
    {
        $manager = $this->authorizeManager();

        return $this->shiftPublicId ? app(ManagerShiftQuery::class)->find($manager, $this->shiftPublicId) : null;
    }

    /** @return array<int, array{movement:\App\Models\CashMovement, invoice:?string, salePublicId:?string}> */
    private function movements(CashierShift $shift): array
    {
        $movements = $shift->cashMovements->sortByDesc('created_at')->values();
        $paymentIds = $movements->where('reference_type', 'payment')->pluck('reference_id')->filter();
        $returnIds = $movements->where('reference_type', 'sale_return')->pluck('reference_id')->filter();
        $payments = $paymentIds->isEmpty() ? collect() : Payment::query()->with('sale')->whereIn('id', $paymentIds)->get()->keyBy('id');
        $returns = $returnIds->isEmpty() ? collect() : SaleReturn::query()->with('sale')->whereIn('id', $returnIds)->get()->keyBy('id');

        return $movements->map(function ($movement) use ($payments, $returns): array {
            $sale = $payments->get($movement->reference_id)?->sale ?? $returns->get($movement->reference_id)?->sale;

            return [
                'movement' => $movement,
                'invoice' => $sale?->invoice_number,
                'salePublicId' => $sale?->public_id,
            ];
        })->all();
    }

    private function authorizeManager(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active && filled($user->store_id), 403);

        return $user;
    }
}
