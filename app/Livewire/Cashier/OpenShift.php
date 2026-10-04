<?php

namespace App\Livewire\Cashier;

use App\Actions\Shift\OpenShiftAction;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class OpenShift extends Component
{
    public int $openingCash = 0;

    public string $openingNote = '';

    public bool $shiftClosed = false;

    public function mount(): void
    {
        $cashier = $this->cashier();

        if (! $cashier->store_id || ! $cashier->store()->exists()) {
            abort(403, 'Akun cashier belum memiliki store aktif.');
        }

        if ($this->activeShift($cashier)) {
            $this->redirect('/cashier/pos');

            return;
        }

        $this->shiftClosed = session()->has('cashier.shift_closed_at');
    }

    public function openShift(): void
    {
        if ($this->shiftClosed) {
            $this->addError('shift', 'Shift sebelumnya sudah ditutup. Keluar dari akun terlebih dahulu.');

            return;
        }

        $cashier = $this->cashier();

        $this->validate([
            'openingCash' => ['required', 'integer', 'min:0'],
            'openingNote' => ['nullable', 'string', 'max:1000'],
        ], [
            'openingCash.required' => 'Kas awal wajib diisi.',
            'openingCash.integer' => 'Kas awal harus berupa angka Rupiah yang valid.',
            'openingCash.min' => 'Kas awal tidak boleh negatif.',
            'openingNote.max' => 'Catatan pembukaan maksimal 1.000 karakter.',
        ]);

        try {
            app(OpenShiftAction::class)->handle(
                $cashier,
                $this->openingCash,
                trim($this->openingNote) !== '' ? trim($this->openingNote) : null,
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field === 'opening_cash' ? 'openingCash' : $field, $message);
                }
            }

            return;
        } catch (AuthorizationException) {
            $this->addError('shift', 'Akun ini tidak berwenang membuka shift.');

            return;
        }

        $this->redirect('/cashier/pos');
    }

    public function render()
    {
        return view('livewire.cashier.open-shift')
            ->layout('components.layouts.cashier', [
                'title' => 'Mulai Shift Kasir',
            ]);
    }

    private function cashier(): User
    {
        $user = request()->user();

        if (! $user instanceof User || $user->role !== UserRole::Cashier || ! $user->is_active) {
            abort(403, 'Akun ini tidak dapat membuka cashier shift.');
        }

        return $user;
    }

    private function activeShift(User $cashier): ?CashierShift
    {
        return CashierShift::query()
            ->where('user_id', $cashier->id)
            ->where('store_id', $cashier->store_id)
            ->where('status', ShiftStatus::Open)
            ->latest('opened_at')
            ->first();
    }

}
