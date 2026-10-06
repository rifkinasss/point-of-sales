<?php

namespace App\Livewire\Owner\Transactions;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Queries\OwnerTransactionQuery;
use App\Services\ActiveStoreContext;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    public string $search = ''; public string $store = 'all'; public string $datePreset = 'today'; public string $dateFrom = ''; public string $dateTo = ''; public string $status = ''; public string $paymentMethod = ''; public string $cashier = '';
    public function mount(): void { $owner = $this->authorizeOwner(); $this->store = app(ActiveStoreContext::class)->identifier($owner) ?: 'all'; }
    public function updated($property): void { if (in_array($property, ['search','store','datePreset','dateFrom','dateTo','status','paymentMethod','cashier'], true)) $this->resetPage(); }
    public function resetFilters(): void { $this->search = ''; $this->store = 'all'; $this->datePreset = 'today'; $this->dateFrom = ''; $this->dateTo = ''; $this->status = ''; $this->paymentMethod = ''; $this->cashier = ''; $this->resetPage(); }
    protected function rules(): array { $owner = $this->authorizeOwner(); $stores = app(ActiveStoreContext::class)->availableStores($owner)->pluck('public_id')->all(); $cashiers = User::query()->where('role', 'cashier')->whereIn('store_id', app(ActiveStoreContext::class)->scope($owner)->ids())->pluck('public_id')->all(); return ['store' => ['required', Rule::in(array_merge(['all'], $stores))], 'datePreset' => ['required', Rule::in(['today','yesterday','last_7_days','last_30_days','custom'])], 'dateFrom' => ['nullable','date_format:Y-m-d'], 'dateTo' => ['nullable','date_format:Y-m-d','after_or_equal:dateFrom'], 'status' => ['nullable', Rule::in(array_column(SaleStatus::cases(), 'value'))], 'paymentMethod' => ['nullable', Rule::in(array_column(PaymentMethod::cases(), 'value'))], 'cashier' => ['nullable', Rule::in($cashiers)]]; }
    public function render() { $owner = $this->authorizeOwner(); $this->validate($this->rules()); $query = app(OwnerTransactionQuery::class); $stores = app(ActiveStoreContext::class)->availableStores($owner); $cashiers = User::query()->where('role', 'cashier')->whereIn('store_id', app(ActiveStoreContext::class)->scope($owner)->ids())->orderBy('name')->get(); return view('livewire.owner.transactions.index', ['transactions' => $query->paginate($owner, $this->filters()), 'summary' => $query->summary($owner, $this->filters()), 'stores' => $stores, 'cashiers' => $cashiers])->layout('components.layouts.owner', ['title' => 'Transaksi', 'activeNav' => 'transactions']); }
    private function filters(): array { return ['search'=>$this->search,'store'=>$this->store,'datePreset'=>$this->datePreset,'dateFrom'=>$this->dateFrom,'dateTo'=>$this->dateTo,'status'=>$this->status,'paymentMethod'=>$this->paymentMethod,'cashier'=>$this->cashier,'perPage'=>25]; }
    private function authorizeOwner(): User { $user = request()->user(); abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403); return $user; }
}
