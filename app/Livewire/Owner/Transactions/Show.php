<?php

namespace App\Livewire\Owner\Transactions;

use App\Enums\UserRole;
use App\Models\Sale;
use App\Models\User;
use App\Queries\OwnerTransactionQuery;
use Livewire\Component;

class Show extends Component
{
    public ?string $salePublicId = null;
    public function mount(string|Sale $sale): void { $this->authorizeOwner(); $this->salePublicId = $sale instanceof Sale ? $sale->public_id : $sale; abort_unless($this->sale instanceof Sale, 404); }
    public function render() { $this->authorizeOwner(); return view('livewire.owner.transactions.show', ['sale'=>$this->sale])->layout('components.layouts.owner', ['title'=>'Detail Transaksi','activeNav'=>'transactions']); }
    public function getSaleProperty(): ?Sale { $owner = $this->authorizeOwner(); return $this->salePublicId ? app(OwnerTransactionQuery::class)->find($owner, $this->salePublicId) : null; }
    private function authorizeOwner(): User { $user=request()->user(); abort_unless($user instanceof User && $user->role===UserRole::Owner && $user->is_active,403); return $user; }
}
