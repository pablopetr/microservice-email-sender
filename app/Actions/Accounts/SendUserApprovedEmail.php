<?php

namespace App\Actions\Accounts;

use App\Mail\UserApprovedMail;
use Illuminate\Support\Facades\Mail;

class SendUserApprovedEmail
{
    public function execute(array $data): void
    {
        Mail::to($data['email'])->send(new UserApprovedMail($data));
    }
}
