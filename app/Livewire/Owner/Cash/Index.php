<?php

namespace App\Livewire\Owner\Cash;

use App\Enums\UserRole;
use App\Models\User;
use App\Queries\OwnerCashQuery;
use App\Services\ActiveStoreContext;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    public string $store='all'; public string $datePreset='last_30_days'; public string $dateFrom=''; public string $dateTo=''; public string $status=''; public string $cashier='';
    public function mount(): void { $owner=$this->authorizeOwner(); $this->store=app(ActiveStoreContext::class)->identifier($owner) ?: 'all'; }
    public function updated($property): void { if (in_array($property,['store','datePreset','dateFrom','dateTo','status','cashier'],true)) $this->resetPage(); }
    public function resetFilters(): void { $this->store='all';$this->datePreset='last_30_days';$this->dateFrom='';$this->dateTo='';$this->status='';$this->cashier='';$this->resetPage(); }
    protected function rules(): array { $owner=$this->authorizeOwner(); $stores=app(ActiveStoreContext::class)->availableStores($owner)->pluck('public_id')->all(); $cashiers=User::query()->where('role','cashier')->whereIn('store_id',app(ActiveStoreContext::class)->scope($owner)->ids())->pluck('public_id')->all(); return ['store'=>['required',Rule::in(array_merge(['all'],$stores))],'datePreset'=>['required',Rule::in(['today','yesterday','last_7_days','last_30_days','custom'])],'dateFrom'=>['nullable','date_format:Y-m-d'],'dateTo'=>['nullable','date_format:Y-m-d','after_or_equal:dateFrom'],'status'=>['nullable',Rule::in(['open','closed'])],'cashier'=>['nullable',Rule::in($cashiers)]]; }
    public function render() { $owner=$this->authorizeOwner();$this->validate($this->rules());$query=app(OwnerCashQuery::class);$scope=app(ActiveStoreContext::class);$filters=$this->filters();return view('livewire.owner.cash.index',['summary'=>$query->summary($owner,$filters),'shifts'=>$query->paginate($owner,$filters),'stores'=>$scope->availableStores($owner),'cashiers'=>User::query()->where('role','cashier')->whereIn('store_id',$scope->scope($owner)->ids())->orderBy('name')->get(),'storeSummaries'=>$query->storeSummaries($owner,$filters),'activeShifts'=>$query->activeShifts($owner,$filters)])->layout('components.layouts.owner',['title'=>'Kas & Rekonsiliasi','activeNav'=>'cash']); }
    private function filters(): array { return ['store'=>$this->store,'datePreset'=>$this->datePreset,'dateFrom'=>$this->dateFrom,'dateTo'=>$this->dateTo,'status'=>$this->status,'cashier'=>$this->cashier,'perPage'=>25]; }
    private function authorizeOwner(): User { $user=request()->user();abort_unless($user instanceof User&&$user->role===UserRole::Owner&&$user->is_active,403);return $user; }
}
