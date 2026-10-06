<?php

namespace App\Livewire\Owner\Reports;

use App\Enums\UserRole;
use App\Models\User;
use App\Queries\OwnerReportQuery;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public string $tab = 'summary';
    public string $store = 'all';
    public string $datePreset = 'last_30_days';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $productSort = 'net';

    public function mount(): void
    {
        $this->authorizeOwner();
    }

    public function updated($property): void
    {
        if (in_array($property, ['store', 'datePreset', 'dateFrom', 'dateTo', 'productSort'], true)) {
            $this->resetValidation();
        }
    }

    public function resetFilters(): void
    {
        $this->store = 'all'; $this->datePreset = 'last_30_days'; $this->dateFrom = ''; $this->dateTo = ''; $this->productSort = 'net'; $this->resetValidation();
    }

    public function render()
    {
        $owner = $this->authorizeOwner();
        $this->validate($this->rules());
        $query = app(OwnerReportQuery::class);
        $stores = $query->authorizedStores($owner);
        abort_unless($this->store === 'all' || $stores->contains('public_id', $this->store), 404);

        return view('livewire.owner.reports.index', ['data' => $query->report($owner, $this->filters()), 'stores' => $stores])->layout('components.layouts.owner', ['title' => 'Laporan & Analitik', 'activeNav' => 'reports']);
    }

    protected function rules(): array
    {
        return ['store' => ['required', Rule::in(array_merge(['all'], app(OwnerReportQuery::class)->authorizedStores($this->authorizeOwner())->pluck('public_id')->all()))], 'datePreset' => ['required', Rule::in(['today', 'yesterday', 'last_7_days', 'last_30_days', 'this_month', 'last_month', 'custom'])], 'dateFrom' => ['nullable', 'date_format:Y-m-d'], 'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'], 'productSort' => ['required', Rule::in(['net', 'quantity'])]];
    }

    /** @return array<string,string> */
    private function filters(): array
    {
        return ['store' => $this->store, 'datePreset' => $this->datePreset, 'dateFrom' => $this->dateFrom, 'dateTo' => $this->dateTo, 'productSort' => $this->productSort];
    }

    private function authorizeOwner(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403);
        return $user;
    }
}
