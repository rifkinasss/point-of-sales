<?php

namespace App\Actions\Shift;

use App\Enums\ShiftStatus;
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
                ->where('status', ShiftStatus::Open)
                ->lockForUpdate()
                ->exists();

            if ($hasOpenShift) {
                throw ValidationException::withMessages([
                    'shift' => 'Kasir masih memiliki shift aktif.',
                ]);
            }

            return CashierShift::query()->create([
                'user_id' => $cashier->id,
                'status' => ShiftStatus::Open,
                'opened_at' => now(),
                'opening_cash' => $openingCash,
                'opening_note' => $note,
            ]);
        });
    }
}
