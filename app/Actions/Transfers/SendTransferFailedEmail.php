<?php

namespace App\Actions\Transfers;

use App\DTO\TransferFailedDTO;
use App\Mail\TransferFailedMail;
use Illuminate\Support\Facades\Mail;

class SendTransferFailedEmail
{
    public function execute(TransferFailedDTO $transferFailedDTO): void
    {
        Mail::to($transferFailedDTO->fromEmail)->send(new TransferFailedMail($transferFailedDTO));
    }
}
