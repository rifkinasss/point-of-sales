<?php

namespace App\Livewire\Manager\Cash;

use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Queries\ManagerCashQuery;
use App\Services\ActiveStoreContext;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $datePreset = 'today';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $status = '';
    public string $cashierId = '';
    public string $variance = '';

    public function mount(): void { $this->authorizeManager(); }
    public function updatedDatePreset(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }
    public function updatedCashierId(): void { $this->resetPage(); }
    public function updatedVariance(): void { $this->resetPage(); }

    public function render()
    {
        $manager = $this->authorizeManager();
        $filters = ['datePreset' => $this->datePreset, 'dateFrom' => $this->dateFrom, 'dateTo' => $this->dateTo, 'status' => $this->status, 'cashierId' => $this->cashierId, 'variance' => $this->variance];

        return view('livewire.manager.cash.index', [
            'summary' => app(ManagerCashQuery::class)->summary($manager, $filters),
            'shifts' => app(ManagerCashQuery::class)->paginate($manager, $filters),
            'cashiers' => User::query()->whereIn('store_id', app(ActiveStoreContext::class)->scope($manager)->ids())->where('role', UserRole::Cashier)->orderBy('name')->get(['id', 'name']),
        ])->layout('components.layouts.manager', ['title' => 'Kas & Rekonsiliasi', 'activeNav' => 'cash']);
    }

    private function authorizeManager(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active && filled($user->store_id), 403);

        return $user;
    }
}
