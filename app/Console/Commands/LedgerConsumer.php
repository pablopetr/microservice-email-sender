<?php

namespace App\Console\Commands;

use App\Actions\Accounts\SendUserApprovedEmail;
use App\Actions\Accounts\SendUserCreatedEmail;
use App\Actions\Transfers\SendTransferCompletedEmails;
use App\Actions\Transfers\SendTransferFailedEmail;
use App\DTO\TransferCompletedDTO;
use App\DTO\TransferFailedDTO;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class LedgerConsumer extends Command
{
    protected $signature = 'ledger:consume
        {queue? : Name of the main queue (default: env LEDGER_QUEUE)}';

    protected $description = 'Consume events from RabbitMQ and dispatch transfer emails';

    public function handle(): int
    {
        $queue = $this->argument('queue') ?? env('LEDGER_QUEUE', 'ledger.transfers.completed');
        $retryQueue = env('LEDGER_RETRY_QUEUE', $queue.'.retry');
        $dlq = env('LEDGER_DLQ', $queue.'.dlq');

        $host = env('RABBITMQ_HOST', '127.0.0.1');
        $port = (int) env('RABBITMQ_PORT', 5672);
        $user = env('RABBITMQ_USER', 'guest');
        $pass = env('RABBITMQ_PASSWORD', 'guest');
        $vhost = env('RABBITMQ_VHOST', '/');

        $this->warn("Connecting to amqp://{$user}@{$host}:{$port}{$vhost} queue={$queue}");

        $conn = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
        $ch = $conn->channel();

        try {
            [$qName, $messageCount, $consumerCount] = $ch->queue_declare($queue, true, true, false, false);
            $this->info("Queue exists: {$qName} | messages={$messageCount} | consumers={$consumerCount}");
        } catch (\PhpAmqpLib\Exception\AMQPProtocolChannelException $e) {
            $this->error("Queue declare(passive) failed: {$e->getMessage()}");
            throw $e;
        }

        $ch->basic_qos(null, 50, null);

        $this->info("Listening {$queue} … (retry={$retryQueue}, dlq={$dlq})");

        $publishRetry = function (string $body) use ($ch, $retryQueue) {
            $msg = new AMQPMessage($body, [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]);
            $ch->basic_publish($msg, '', $retryQueue);
        };

        $callback = function (AMQPMessage $msg) use ($ch, $publishRetry) {
            $this->line(" [x] deliveryTag={$msg->getDeliveryTag()} bytes=".strlen($msg->getBody()));
            $body = $msg->getBody();

            try {
                $evt = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

                $eventId = $evt['event_id'] ?? $evt['idempotency_key'] ?? null;

                if (! $eventId) {
                    throw new \RuntimeException('event_id/idempotency_key ausente');
                }

                if (DB::table('event_dedupe')->where('event_id', $eventId)->exists()) {
                    $ch->basic_ack($msg->getDeliveryTag());

                    return;
                }

                if ($evt['event_type'] === 'payments.transfers.completed') {
                    $transferCompletedDTO = TransferCompletedDTO::fromEvent($evt);

                    (new SendTransferCompletedEmails)->execute($transferCompletedDTO);
                }

                if ($evt['event_type'] === 'payments.transfers.failed') {
                    $transferFailedDTO = TransferFailedDTO::fromEvent($evt);

                    (new SendTransferFailedEmail)->execute($transferFailedDTO);
                }

                if ($evt['event_type'] === 'accounts.users.created') {
                    (new SendUserCreatedEmail)->execute($evt['data']);
                }

                if ($evt['event_type'] === 'accounts.users.approved') {
                    (new SendUserApprovedEmail)->execute($evt['data']);
                }

                $ch->basic_ack($msg->getDeliveryTag());
            } catch (\Throwable $e) {
                $this->error('     ERROR: '.$e->getMessage());
                report($e);

                try {
                    $publishRetry($body);
                    $ch->basic_ack($msg->getDeliveryTag());
                } catch (\Throwable $inner) {
                    report($inner);
                    $ch->basic_nack($msg->getDeliveryTag(), false, false);
                }
            }
        };

        $consumerTag = 'ledger-consumer-'.getmypid().'-'.bin2hex(random_bytes(3));
        $ch->basic_consume($queue, $consumerTag, false, false, false, false, $callback);
        $this->info("Consumer tag: {$consumerTag}");

        try {
            while ($ch->is_consuming()) {
                $ch->wait();
                $this->output->write('.');
            }
        } catch (\Throwable $loopErr) {
            $this->error('Loop error: '.$loopErr->getMessage());
            throw $loopErr;
        } finally {
            try {
                $ch->close();
            } catch (\Throwable $e) {
            }
            try {
                $conn->close();
            } catch (\Throwable $e) {
            }
        }

        return self::SUCCESS;
    }
}
