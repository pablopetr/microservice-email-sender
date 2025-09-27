<?php

namespace App\Handlers;

use Illuminate\Support\Facades\Log;

class TransferCompletedHandler
{
    public function handle(string $raw): void
    {
        Log::info('[CONSUMED] payments.transfers.completed', $raw);
    }
}
