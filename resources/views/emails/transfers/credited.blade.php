@php use Carbon\Carbon; @endphp
@component('mail::message')
    # You have received a transfer

    Hello, {{ $recipientName }},

    You have received a transfer of **{{ $amount }}** (from account: {{ $fromLabel }}).


    - Date/time: {{ Carbon::parse($occurredAtIso)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}

    Regards,
    {{ config('app.name') }}
@endcomponent
