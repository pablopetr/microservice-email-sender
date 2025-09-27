<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransferCreditedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $amount,
        public string $fromLabel,
        public string $occurredAtIso
    ) {}

    public function build(): TransferCreditedMail
    {
        return $this->subject('Transfers Received')
            ->markdown('emails.transfers.credited');
    }
}
