<?php

namespace App\Livewire\Manager\Transactions;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Queries\ManagerTransactionQuery;
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
    public string $paymentMethod = '';

    public function mount(): void
    {
        $this->authorizeManager();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedDatePreset(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->validateOnly('dateFrom'); $this->resetPage(); }
    public function updatedDateTo(): void { $this->validateOnly('dateTo'); $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }
    public function updatedPaymentMethod(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'paymentMethod', 'dateFrom', 'dateTo']);
        $this->datePreset = 'today';
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'datePreset' => ['required', Rule::in(['today', 'yesterday', 'last_7_days', 'custom'])],
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            'status' => ['nullable', Rule::in(array_column(SaleStatus::cases(), 'value'))],
            'paymentMethod' => ['nullable', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
        ];
    }

    public function render()
    {
        $manager = $this->authorizeManager();

        return view('livewire.manager.transactions.index', [
            'transactions' => app(ManagerTransactionQuery::class)->paginate($manager, [
                'search' => $this->search,
                'datePreset' => $this->datePreset,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'status' => $this->status,
                'paymentMethod' => $this->paymentMethod,
                'perPage' => 20,
            ]),
        ])->layout('components.layouts.manager', ['title' => 'Transaksi', 'activeNav' => 'transactions']);
    }

    private function authorizeManager(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active && filled($user->store_id), 403);

        return $user;
    }
}
