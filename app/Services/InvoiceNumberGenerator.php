<?php

namespace App\Services;

use Illuminate\Support\Str;

class InvoiceNumberGenerator
{
    public function generate(string $prefix = 'POS'): string
    {
        return strtoupper($prefix).'-'.now()->format('Ymd').'-'.strtoupper((string) Str::ulid());
    }
}
