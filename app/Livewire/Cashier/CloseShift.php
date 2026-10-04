<?php

namespace App\Livewire\Cashier;

use App\Actions\Cash\CreateCashInAction;
use App\Actions\Cash\CreateCashOutAction;
use App\Actions\Shift\CloseShiftAction;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\User;
use App\Services\CashBalanceCalculator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CloseShift extends Component
{
    public ?CashierShift $shift = null;

    public string $closingCashActual = '';

    public string $closingNote = '';

    public string $movementType = 'cash_in';

    public string $movementAmount = '';

    public string $movementNote = '';

    public bool $movementModalOpen = false;

    public ?string $message = null;

    public function mount(): void
    {
        $this->shift = $this->currentShift();
        $this->message = session('shift_guard');
    }

    public function closeShift(): void
    {
        if (! $this->shift) {
            $this->addError('shift', 'Eror: tidak ada shift aktif untuk ditutup.');

            return;
        }

        $this->validate([
            'closingCashActual' => ['required', 'integer', 'min:0'],
            'closingNote' => ['nullable', 'string', 'max:1000'],
        ], [
            'closingCashActual.required' => 'Kas fisik wajib diisi.',
            'closingCashActual.integer' => 'Kas fisik harus berupa angka.',
            'closingCashActual.min' => 'Kas fisik tidak boleh negatif.',
        ]);

        try {
            app(CloseShiftAction::class)->handle(
                $this->shift,
                $this->currentUser(),
                (int) $this->closingCashActual,
                trim($this->closingNote) !== '' ? trim($this->closingNote) : null,
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field === 'closing_cash_actual' ? 'closingCashActual' : $field, $message);
                }
            }

            return;
        }

        session()->put('cashier.shift_closed_at', now()->toIso8601String());
        $this->redirect('/cashier/open-shift');
    }

    public function recordCashMovement(): void
    {
        if (! $this->shift) {
            $this->addError('shift', 'Tidak ada shift aktif.');

            return;
        }

        $data = $this->validate([
            'movementType' => ['required', 'in:cash_in,cash_out'],
            'movementAmount' => ['required', 'integer', 'min:1'],
            'movementNote' => ['required', 'string', 'max:255'],
        ]);

        $action = $data['movementType'] === 'cash_in'
            ? CreateCashInAction::class
            : CreateCashOutAction::class;

        try {
            app($action)->handle($this->shift, $this->currentUser(), (int) $data['movementAmount'], $data['movementNote']);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        $this->reset(['movementAmount', 'movementNote']);
        $this->shift = $this->currentShift();
        $this->message = 'Pergerakan kas berhasil dicatat.';
        $this->movementModalOpen = false;
    }

    public function openMovementModal(string $type = 'cash_in'): void
    {
        $this->resetValidation();
        $this->movementType = in_array($type, ['cash_in', 'cash_out'], true) ? $type : 'cash_in';
        $this->movementModalOpen = true;
    }

    public function closeMovementModal(): void
    {
        $this->resetValidation();
        $this->movementModalOpen = false;
    }

    /** @return array{opening: int, sales: int, cashIn: int, refunds: int, cashOut: int, expected: int} */
    public function getCashSummaryProperty(): array
    {
        return $this->shift
            ? app(CashBalanceCalculator::class)->summary($this->shift)
            : ['opening' => 0, 'sales' => 0, 'cashIn' => 0, 'refunds' => 0, 'cashOut' => 0, 'expected' => 0];
    }

    public function render()
    {
        return view('livewire.cashier.close-shift')->layout('components.layouts.cashier', ['title' => 'Tutup Shift']);
    }

    private function currentUser(): User
    {
        $user = request()->user();

        abort_unless($user instanceof User && $user->role === UserRole::Cashier && $user->is_active, 403);

        return $user;
    }

    private function currentShift(): ?CashierShift
    {
        return CashierShift::query()
            ->where('user_id', $this->currentUser()->id)
            ->where('store_id', $this->currentUser()->store_id)
            ->where('status', ShiftStatus::Open)
            ->latest('opened_at')
            ->first();
    }
}
