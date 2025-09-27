@component('mail::message')
    # Welcome, {{ $data['name'] }}!

    Your account has been **successfully created** and is now **pending approval**.

    Our team will review it shortly. You’ll receive another email once your account is approved.

    @component('mail::panel')
        **Name:** {{ $data['name'] }}
        **Email:** {{ $data['email'] }}
    @endcomponent

    If you didn’t sign up for this account, you can safely ignore this message.

    Thanks,
    The Team

    @slot('subcopy')
        This is an automated message. Please do not reply.
    @endslot

    Regards,
    {{ config('app.name') }}
@endcomponent
