<?php

namespace App\Livewire\Manager\Reports;

use App\Enums\UserRole;
use App\Models\User;
use App\Queries\ManagerReportQuery;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public string $report = 'overview';
    public string $datePreset = 'today';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $cashier = '';
    public string $productSort = 'revenue';

    public function mount(string $report = 'overview'): void
    {
        $this->authorizeManager();
        abort_unless(in_array($report, ['overview', 'sales', 'payments', 'shifts', 'cash', 'products', 'inventory'], true), 404);
        $this->report = $report;
    }

    public function updatedDatePreset(): void { $this->resetValidation(); }
    public function updatedDateFrom(): void { $this->resetValidation(); }
    public function updatedDateTo(): void { $this->resetValidation(); }

    public function resetFilters(): void
    {
        $this->datePreset = 'today';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->cashier = '';
        $this->productSort = 'revenue';
        $this->resetValidation();
    }

    public function exportCsv()
    {
        $this->validate($this->rules());
        abort_if($this->report === 'overview', 404);
        $manager = $this->authorizeManager();
        $data = app(ManagerReportQuery::class)->report($manager, $this->report, $this->filters());
        $columns = collect($data['columns'])->filter(fn ($label): bool => filled($label));
        $rows = $data['rows'];
        $filename = 'naslabs-'.$this->report.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($columns, $rows): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns->values()->all());
            foreach ($rows as $row) {
                fputcsv($handle, $columns->keys()->map(fn ($key) => $this->csvValue($row[$key] ?? null))->all());
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $manager = $this->authorizeManager();
        $query = app(ManagerReportQuery::class);

        return view('livewire.manager.reports.index', [
            'data' => $query->report($manager, $this->report, $this->filters()),
            'cashiers' => User::query()->where('store_id', $manager->store_id)->where('role', UserRole::Cashier)->orderBy('name')->get(['name']),
        ])->layout('components.layouts.manager', ['title' => $this->report === 'overview' ? 'Laporan' : ($query->report($manager, $this->report, $this->filters())['title'] ?? 'Laporan'), 'activeNav' => 'reports']);
    }

    protected function rules(): array
    {
        return [
            'datePreset' => ['required', Rule::in(['today', 'yesterday', 'last_7_days', 'last_30_days', 'this_month', 'custom'])],
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            'productSort' => ['required', Rule::in(['revenue', 'quantity', 'lowest_quantity'])],
        ];
    }

    /** @return array<string, string> */
    private function filters(): array
    {
        return ['datePreset' => $this->datePreset, 'dateFrom' => $this->dateFrom, 'dateTo' => $this->dateTo, 'cashier' => $this->cashier, 'productSort' => $this->productSort];
    }

    private function csvValue(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        return is_scalar($value) || $value === null ? (string) ($value ?? '') : Str::of(json_encode($value))->toString();
    }

    private function authorizeManager(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active && filled($user->store_id), 403);

        return $user;
    }
}
