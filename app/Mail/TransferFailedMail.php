<?php

namespace App\Mail;

use App\DTO\TransferFailedDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransferFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        TransferFailedDTO $transferFailedDTO
    )
    {
    }

    public function build(): TransferFailedMail
    {
        return $this->subject('Transfer Failed Notification')
                    ->view('emails.transfers.failed');
    }
}
