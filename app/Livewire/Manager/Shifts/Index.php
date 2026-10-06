<?php

namespace App\Livewire\Manager\Shifts;

use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Queries\ManagerShiftQuery;
use App\Services\ActiveStoreContext;
use App\Services\CashBalanceCalculator;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $datePreset = 'today';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $status = '';
    public string $cashierId = '';
    public string $variance = '';

    public function mount(): void { $this->authorizeManager(); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedDatePreset(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }
    public function updatedCashierId(): void { $this->resetPage(); }
    public function updatedVariance(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'dateFrom', 'dateTo', 'status', 'cashierId', 'variance']);
        $this->datePreset = 'today';
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'datePreset' => ['required', Rule::in(['today', 'yesterday', 'last_7_days', 'custom'])],
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            'status' => ['nullable', Rule::in(['', ...array_column(ShiftStatus::cases(), 'value')])],
            'variance' => ['nullable', Rule::in(['', 'matching', 'nonzero'])],
        ];
    }

    public function render()
    {
        $manager = $this->authorizeManager();

        $shifts = app(ManagerShiftQuery::class)->paginate($manager, [
                'search' => $this->search, 'datePreset' => $this->datePreset,
                'dateFrom' => $this->dateFrom, 'dateTo' => $this->dateTo,
                'status' => $this->status, 'cashierId' => $this->cashierId,
                'variance' => $this->variance, 'perPage' => 20,
            ]);
        $summaries = app(CashBalanceCalculator::class)->summaries(collect($shifts->items()));
        foreach ($shifts->getCollection() as $shift) {
            $shift->setAttribute('cash_summary', $summaries->get($shift->id));
        }

        return view('livewire.manager.shifts.index', [
            'shifts' => $shifts,
            'metrics' => app(ManagerShiftQuery::class)->metrics($manager, ['datePreset' => $this->datePreset, 'dateFrom' => $this->dateFrom, 'dateTo' => $this->dateTo]),
            'cashiers' => User::query()->whereIn('store_id', app(ActiveStoreContext::class)->scope($manager)->ids())->where('role', UserRole::Cashier)->orderBy('name')->get(['id', 'name']),
        ])->layout('components.layouts.manager', ['title' => 'Shift Kasir', 'activeNav' => 'shifts']);
    }

    private function authorizeManager(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active && filled($user->store_id), 403);

        return $user;
    }
}
