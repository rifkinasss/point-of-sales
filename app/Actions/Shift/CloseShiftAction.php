<?php

namespace App\Actions\Shift;

use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\User;
use App\Services\CashBalanceCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseShiftAction
{
    public function handle(CashierShift $shift, User $closedBy, int $closingCashActual, ?string $note = null): CashierShift
    {
        if ($closingCashActual < 0) {
            throw ValidationException::withMessages([
                'closing_cash_actual' => 'Kas fisik tidak boleh negatif.',
            ]);
        }

        return DB::transaction(function () use ($closedBy, $closingCashActual, $note, $shift): CashierShift {
            $shift = CashierShift::query()->lockForUpdate()->findOrFail($shift->id);

            if ($shift->status !== ShiftStatus::Open) {
                throw ValidationException::withMessages([
                    'shift' => 'Hanya shift aktif yang dapat ditutup.',
                ]);
            }

            if ($shift->user_id !== $closedBy->id || ($shift->store_id && $closedBy->store_id && $shift->store_id !== $closedBy->store_id)) {
                throw ValidationException::withMessages([
                    'shift' => 'Shift hanya dapat ditutup oleh pemilik pada toko yang sama.',
                ]);
            }

            $expectedCash = app(CashBalanceCalculator::class)->summary($shift)['expected'];
            $difference = $closingCashActual - $expectedCash;

            if ($difference !== 0 && blank($note)) {
                throw ValidationException::withMessages([
                    'closing_note' => 'Catatan wajib diisi bila ada selisih kas.',
                ]);
            }

            $shift->update([
                'status' => ShiftStatus::Closed,
                'closed_at' => now(),
                'closing_cash_actual' => $closingCashActual,
                'expected_cash' => $expectedCash,
                'cash_difference' => $difference,
                'closing_note' => $note,
                'closed_by' => $closedBy->id,
            ]);

            return $shift->refresh();
        });
    }
}
