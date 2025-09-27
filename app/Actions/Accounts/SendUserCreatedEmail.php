<?php

namespace App\Actions\Accounts;

use App\Mail\UserCreatedMail;
use Illuminate\Support\Facades\Mail;

class SendUserCreatedEmail
{
    public function execute(array $data): void
    {
        Mail::to($data['email'])->send(new UserCreatedMail($data));
    }
}
