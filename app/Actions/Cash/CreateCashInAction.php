<?php

namespace App\Actions\Cash;

use App\Enums\CashMovementType;
use App\Enums\ShiftStatus;
use App\Models\CashMovement;
use App\Models\CashierShift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCashInAction
{
    public function handle(CashierShift $shift, User $actor, int $amount, string $note): CashMovement
    {
        return $this->createMovement($shift, $actor, $amount, $note, CashMovementType::CashIn);
    }

    protected function createMovement(CashierShift $shift, User $actor, int $amount, string $note, CashMovementType $type): CashMovement
    {
        if ($amount < 1) {
            throw ValidationException::withMessages(['amount' => 'Nominal kas harus lebih dari nol.']);
        }
        if (blank($note)) {
            throw ValidationException::withMessages(['note' => 'Catatan kas wajib diisi.']);
        }

        return DB::transaction(function () use ($actor, $amount, $note, $shift, $type): CashMovement {
            $shift = CashierShift::query()->lockForUpdate()->findOrFail($shift->id);
            $this->assertOwnedOpenShift($shift, $actor);

            return CashMovement::query()->create([
                'store_id' => $shift->store_id,
                'shift_id' => $shift->id,
                'user_id' => $actor->id,
                'type' => $type,
                'amount' => $amount,
                'note' => $note,
            ]);
        });
    }

    protected function assertOwnedOpenShift(CashierShift $shift, User $actor): void
    {
        if ($shift->status !== ShiftStatus::Open || $shift->user_id !== $actor->id || ($shift->store_id && $actor->store_id && $shift->store_id !== $actor->store_id)) {
            throw ValidationException::withMessages(['shift' => 'Shift aktif milik actor dan toko yang benar diperlukan.']);
        }
    }
}
