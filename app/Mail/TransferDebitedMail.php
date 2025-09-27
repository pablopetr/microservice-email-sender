<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransferDebitedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $amount,
        public string $toLabel,
        public string $occurredAtIso
    ) {}

    public function build(): TransferDebitedMail
    {
        return $this->subject('Transfers Sent')
            ->markdown('emails.transfers.debited');
    }
}
