<?php

namespace App\Livewire\Owner\Cash;

use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\User;
use App\Queries\OwnerCashQuery;
use App\Services\CashBalanceCalculator;
use Livewire\Component;

class Show extends Component
{
    public ?string $shiftPublicId=null;
    public function mount(string|CashierShift $shift): void { $this->authorizeOwner();$this->shiftPublicId=$shift instanceof CashierShift?$shift->public_id:$shift;abort_unless($this->shift instanceof CashierShift,404); }
    public function render() { $this->authorizeOwner();$shift=$this->shift;abort_unless($shift,404);return view('livewire.owner.cash.show',['shift'=>$shift,'summary'=>app(CashBalanceCalculator::class)->summary($shift),'movements'=>app(OwnerCashQuery::class)->movements($shift)])->layout('components.layouts.owner',['title'=>'Detail Rekonsiliasi','activeNav'=>'cash']); }
    public function getShiftProperty(): ?CashierShift { $owner=$this->authorizeOwner();return $this->shiftPublicId?app(OwnerCashQuery::class)->find($owner,$this->shiftPublicId):null; }
    private function authorizeOwner(): User { $user=request()->user();abort_unless($user instanceof User&&$user->role===UserRole::Owner&&$user->is_active,403);return $user; }
}
