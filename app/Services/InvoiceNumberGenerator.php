<?php

namespace App\Services;

use App\Models\InvoiceSequence;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceNumberGenerator
{
    public function generate(Store $store): string
    {
        if (! preg_match('/^[A-Z0-9]{3}$/', (string) $store->store_code)) {
            throw ValidationException::withMessages(['store_code' => 'Kode toko harus terdiri dari 3 karakter alfanumerik uppercase.']);
        }

        if (filled($store->branch_code) && ! preg_match('/^[A-Z0-9]{1,7}$/', (string) $store->branch_code)) {
            throw ValidationException::withMessages(['branch_code' => 'Kode cabang maksimal 7 karakter alfanumerik uppercase.']);
        }

        $timezone = $store->timezone ?: config('app.timezone');
        $businessDate = CarbonImmutable::now($timezone)->toDateString();
        $branchCode = $store->branch_code ?: null;
        $scopeKey = implode('|', [$store->getKey(), $branchCode ?? '-', $businessDate]);

        $sequence = DB::transaction(function () use ($store, $branchCode, $businessDate, $scopeKey): int {
            DB::table('invoice_sequences')->insertOrIgnore([
                'store_id' => $store->getKey(),
                'branch_code' => $branchCode,
                'business_date' => $businessDate,
                'scope_key' => $scopeKey,
                'last_sequence' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = InvoiceSequence::query()->where('scope_key', $scopeKey)->lockForUpdate()->first();

            if (! $row) {
                throw ValidationException::withMessages(['invoice_number' => 'Sequence invoice tidak dapat dibuat.']);
            }

            $next = $row->last_sequence + 1;
            $row->update(['last_sequence' => $next]);

            return $next;
        });

        $date = str_replace('-', '', $businessDate);
        $parts = [$store->store_code];

        if ($branchCode) {
            $parts[] = $branchCode;
        }

        return implode('-', [...$parts, $date, str_pad((string) $sequence, 6, '0', STR_PAD_LEFT)]);
    }
}
