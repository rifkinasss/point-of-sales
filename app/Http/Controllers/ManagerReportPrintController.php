<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Queries\ManagerReportQuery;
use Illuminate\View\View;

class ManagerReportPrintController
{
    public function __invoke(string $report, ManagerReportQuery $query): View
    {
        $manager = request()->user();
        abort_unless($manager instanceof User && $manager->role === UserRole::Manager && $manager->is_active && filled($manager->store_id), 403);
        abort_unless(in_array($report, ['sales', 'payments', 'shifts', 'cash', 'products', 'inventory'], true), 404);

        return view('manager.reports.print', [
            'data' => $query->report($manager, $report, ['datePreset' => request()->string('date_preset', 'today')->toString(), 'dateFrom' => request()->string('date_from')->toString(), 'dateTo' => request()->string('date_to')->toString()]),
            'store' => $manager->store,
        ]);
    }
}
