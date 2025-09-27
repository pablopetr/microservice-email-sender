<?php

namespace App\Actions\Transfers;

use App\DTO\TransferCompletedDTO;
use App\Mail\TransferCreditedMail;
use App\Mail\TransferDebitedMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendTransferCompletedEmails
{
    public function execute(TransferCompletedDTO $transferCompletedDTO): void
    {
        Mail::to($transferCompletedDTO->toUserEmail)->send(new TransferCreditedMail(
            recipientName: $transferCompletedDTO->fromUserName,
            amount: $transferCompletedDTO->amount,
            fromLabel: $transferCompletedDTO->fromAccountNumber,
            occurredAtIso: $transferCompletedDTO->occurredAt
        ));

        Mail::to($transferCompletedDTO->fromUserEmail)->send(new TransferDebitedMail(
            senderName: $transferCompletedDTO->toUserName,
            amount: $transferCompletedDTO->amount,
            toLabel: $transferCompletedDTO->toAccountNumber,
            occurredAtIso: $transferCompletedDTO->occurredAt
        ));

        DB::table('event_dedupe')->insert(['event_id' => $transferCompletedDTO->eventId, 'received_at' => now()]);
    }
}
