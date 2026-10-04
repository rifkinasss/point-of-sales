<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReceiptMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Sale $sale) {}

    public function build(): static
    {
        return $this
            ->subject("Struk {$this->sale->invoice_number}")
            ->view('mail.receipt');
    }
}
