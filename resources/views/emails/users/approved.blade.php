@component('mail::message')
    # Your account is approved, {{ $data['name'] }}!

    Great news — your account has been **approved**. You can now sign in and start using all features.

    @component('mail::panel')
        **Name:** {{ $data['name'] }}
        **Email:** {{ $data['email'] }}
    @endcomponent

    If you didn’t request this account, you can safely ignore this message.

    Thanks,
    The Team

    @slot('subcopy')
        This is an automated message. Please do not reply.
    @endslot

    Regards,
    {{ config('app.name') }}
@endcomponent
