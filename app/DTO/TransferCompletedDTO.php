<?php

namespace App\DTO;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final readonly class TransferCompletedDTO
{
    public function __construct(
        public string $eventId,
        public string $amount,
        public string $fromAccountNumber,
        public string $toAccountNumber,
        public string $occurredAt,
        public string $fromUserEmail,
        public string $toUserEmail,
        public string $fromUserName,
        public string $toUserName,
    ) {
    }

    public static function fromEvent(array $event): self
    {
        $data = $event['data'] ?? [];

        $payload = [
            'event_id'            => (string) ($event['event_id'] ?? $data['idempotency_key'] ?? ''),
            'transfer_id'         => (string) ($data['transfer_id'] ?? ''),
            'amount'              => (string) ($data['amount'] ?? '0.00'),
            'currency'            => (string) ($data['currency'] ?? 'BRL'),
            'from_account_number' => (string) ($data['from_account_number'] ?? 'n/a'),
            'to_account_number'   => (string) ($data['to_account_number'] ?? 'n/a'),
            'from_user_name'      => (string) ($data['from_user_name'] ?? 'client'),
            'to_user_name'        => (string) ($data['to_user_name'] ?? 'client'),
            'from_user_email'     => Arr::get($data, 'from_user_email'),
            'to_user_email'       => Arr::get($data, 'to_user_email'),
            'occurred_at'         => (string) ($evt['occurred_at'] ?? now()->toIso8601String()),
        ];

        $validator = Validator::make($payload, [
            'event_id'            => ['required', 'string'],
            'transfer_id'         => ['nullable', 'string'],
            'amount'              => ['required', 'string'],
            'currency'            => ['required', 'string', 'size:3'],
            'from_account_number' => ['required', 'string'],
            'to_account_number'   => ['required', 'string'],
            'from_user_name'      => ['required', 'string'],
            'to_user_name'        => ['required', 'string'],
            'from_user_email'     => ['required', 'email'],
            'to_user_email'       => ['required', 'email'],
            'occurred_at'         => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $occurredAt = CarbonImmutable::parse($payload['occurred_at']);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('occurred_at inválido: '.$e->getMessage(), previous: $e);
        }

        return new self(
            eventId: $payload['event_id'],
            amount: $payload['amount'],
            fromAccountNumber: $payload['from_account_number'],
            toAccountNumber: $payload['to_account_number'],
            occurredAt: $occurredAt,
            fromUserEmail: $payload['from_user_email'],
            toUserEmail: $payload['to_user_email'],
            fromUserName: $payload['from_user_name'],
            toUserName: $payload['to_user_name'],
        );
    }
}
