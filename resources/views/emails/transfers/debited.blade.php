@php use Carbon\Carbon; @endphp
@component('mail::message')
    # Your transfer has been sent

    Hello, {{ $senderName }},

    You have sent **{{ $amount }}** (destination: {{ $toLabel }}).

    - Date/time: {{ Carbon::parse($occurredAtIso)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}

    If you do not recognize this transaction, please contact support.

    Thank you,<br>
    {{ config('app.name') }}
@endcomponent
