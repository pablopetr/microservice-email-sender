<?php

namespace App\DTO;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

readonly class TransferFailedDTO
{
    public string $eventType;
    public string $eventId;
    public ?string $transferId;
    public ?string $status;
    public ?string $amount;
    public ?string $fromAccountNumber;
    public ?string $toAccountNumber;
    public ?string $fromUserName;
    public string $fromEmail;
    public ?int $fromAccountId;
    public CarbonImmutable $occurredAt;

    private function __construct(
        string $eventId,
        string $transferId,
        string $status,
        string $amount,
        string $fromAccountNumber,
        string $toAccountNumber,
        string $fromUserName,
        string $fromEmail,
        int $fromAccountId,
        CarbonImmutable $occurredAt,
        string $eventType = 'payments.transfers.failed',
    ) {
        $this->eventType = $eventType;
        $this->eventId = $eventId;
        $this->transferId = $transferId;
        $this->status = $status;
        $this->amount = $amount;
        $this->fromAccountNumber = $fromAccountNumber;
        $this->toAccountNumber = $toAccountNumber;
        $this->fromUserName = $fromUserName;
        $this->fromEmail = $fromEmail;
        $this->fromAccountId = $fromAccountId;
        $this->occurredAt = $occurredAt;
    }

    public static function fromEvent(array $evt): self
    {
        $data = $evt['data'] ?? [];

        $payload = [
            'event_id'            => $data['idempotency_key'] ?? '',
            'transfer_id'         => Arr::get($data, 'transfer_id'),
            'status'              => Arr::get($data, 'status'),
            'amount'              => Arr::get($data, 'amount'),
            'from_account_number' => Arr::get($data, 'from_account_number'),
            'to_account_number'   => Arr::get($data, 'to_account_number'),
            'from_user_name'      => Arr::get($data, 'from_user_name'),
            'from_user_email'     => Arr::get($data, 'from_user_email'),
            'from_account'        => Arr::get($data, 'from_account'),
            'occurred_at'         => (string)($evt['occurred_at'] ?? now()->toIso8601String()),
        ];

        $validator = Validator::make($payload, [
            'event_id'            => ['required', 'string'],
            'from_user_email'     => ['required', 'email'],
            'occurred_at'         => ['required', 'string'],
            'transfer_id'         => ['nullable', 'string'],
            'status'              => ['nullable', 'string'],
            'amount'              => ['nullable', 'string'],
            'from_account_number' => ['nullable', 'string'],
            'to_account_number'   => ['nullable', 'string'],
            'from_user_name'      => ['nullable', 'string'],
            'from_account'        => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            dump('Failed on TransferFailedDTO validation', $validator->errors()->all(), $payload);
            throw new ValidationException($validator);
        }

        try {
            $occurredAt = CarbonImmutable::parse($payload['occurred_at']);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('occurred_at inválido: '.$e->getMessage(), previous: $e);
        }

        return new self(
            eventId:            $payload['event_id'],
            transferId:         $payload['transfer_id'],
            status:             $payload['status'],
            amount:             $payload['amount'],
            fromAccountNumber:  $payload['from_account_number'],
            toAccountNumber:    $payload['to_account_number'],
            fromUserName:       $payload['from_user_name'],
            fromEmail:          $payload['from_user_email'],
            fromAccountId:      $payload['from_account'],
            occurredAt:         $occurredAt,
        );
    }

    public function isFailed(): bool
    {
        return $this->eventType === 'payments.transfers.failed';
    }

    public function toArray(): array
    {
        return [
            'event_id'            => $this->eventId,
            'transfer_id'         => $this->transferId,
            'status'              => $this->status,
            'amount'              => $this->amount,
            'from_account_number' => $this->fromAccountNumber,
            'to_account_number'   => $this->toAccountNumber,
            'from_user_name'      => $this->fromUserName,
            'from_user_email'     => $this->fromEmail,
            'from_account'        => $this->fromAccountId,
            'occurred_at'         => $this->occurredAt->toIso8601String(),
        ];
    }
}
