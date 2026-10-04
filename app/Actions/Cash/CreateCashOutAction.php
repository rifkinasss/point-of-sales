<?php

namespace App\Actions\Cash;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Models\CashierShift;
use App\Models\User;

class CreateCashOutAction extends CreateCashInAction
{
    public function handle(CashierShift $shift, User $actor, int $amount, string $note): CashMovement
    {
        return parent::createMovement($shift, $actor, $amount, $note, CashMovementType::CashOut);
    }
}
