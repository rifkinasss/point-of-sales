<?php

namespace App\Actions\Shift;

use App\Enums\ShiftStatus;
use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Models\CashierShift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpenShiftAction
{
    public function handle(User $cashier, int $openingCash, ?string $note = null): CashierShift
    {
        if ($openingCash < 0) {
            throw ValidationException::withMessages([
                'opening_cash' => 'Kas awal tidak boleh negatif.',
            ]);
        }

        return DB::transaction(function () use ($cashier, $note, $openingCash): CashierShift {
            $hasOpenShift = CashierShift::query()
                ->where('user_id', $cashier->id)
                ->where('store_id', $cashier->store_id)
                ->where('status', ShiftStatus::Open)
                ->lockForUpdate()
                ->exists();

            if ($hasOpenShift) {
                throw ValidationException::withMessages([
                    'shift' => 'Kasir masih memiliki shift aktif.',
                ]);
            }

            $shift = CashierShift::query()->create([
                'store_id' => $cashier->store_id,
                'user_id' => $cashier->id,
                'status' => ShiftStatus::Open,
                'opened_at' => now(),
                'opening_cash' => $openingCash,
                'opening_note' => $note,
            ]);

            CashMovement::query()->create([
                'store_id' => $shift->store_id,
                'shift_id' => $shift->id,
                'user_id' => $cashier->id,
                'type' => CashMovementType::Opening,
                'amount' => $openingCash,
                'note' => $note,
            ]);

            return $shift;
        });
    }
}
