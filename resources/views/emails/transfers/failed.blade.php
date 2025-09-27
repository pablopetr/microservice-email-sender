@php
    $currency = $currency ?: 'BRL';
@endphp

<p>Olá {{ $fromUserName }},</p>

<p>Sua solicitação de transferência <strong>não foi concluída</strong>.</p>

<ul>
    <li><strong>ID da transferência:</strong> {{ $transferId ?? '—' }}</li>
    <li><strong>Status:</strong> {{ $status ?? 'failed' }}</li>
    <li><strong>Valor:</strong> {{ $amount ?? '0.00' }} {{ $currency }}</li>
    <li><strong>Conta de origem:</strong> {{ $fromAccountNumber ?? '—' }}</li>
    <li><strong>Conta de destino:</strong> {{ $toAccountNumber ?? '—' }}</li>
    <li><strong>Ocorrida em:</strong> {{ $occurredAtIso }}</li>
</ul>

<p>Se você não reconhece esta operação ou precisa de ajuda, entre em contato com o suporte.</p>

<p>— Equipe do Banco</p>
