<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitProvision extends Command
{
    protected $signature = 'rabbit:provision
        {queue=dev.ledger.transfers.completed : Nome da fila principal}
        {routingKey=payments.transfers.completed : Routing key para bind}
        {exchange=app.events : Exchange topic}
        {--with-dlx : Cria/usa DLX e DLQ}
        {--dlx=app.dlx : Nome do DLX (direct)}
        {--dlq-suffix=.dlq : Sufixo da DLQ}
        {--with-retry : Cria fila de retry com TTL que volta pra principal}
        {--retry-ttl=30000 : TTL (ms) da fila de retry}
    ';

    protected $description = 'Declara exchange topic, fila e binding no RabbitMQ (idempotente: passive-then-declare).';

    public function handle(): int
    {
        $h = config('queue.connections.rabbitmq.hosts.0');

        $conn = new AMQPStreamConnection(
            $h['host'], $h['port'], $h['user'], $h['password'], $h['vhost']
        );
        $ch = $conn->channel();

        $exchange = $this->argument('exchange');
        $queue = $this->argument('queue');
        $rk = $this->argument('routingKey');

        $withDlx = (bool) $this->option('with-dlx');
        $dlxName = (string) $this->option('dlx');
        $dlqSuffix = (string) $this->option('dlq-suffix');
        $withRetry = (bool) $this->option('with-retry');
        $retryTtl = (int) $this->option('retry-ttl');

        $dlq = $queue.$dlqSuffix;
        $retryQueue = $queue.'.retry';

        // 1) Exchange principal (topic)
        $ch->exchange_declare($exchange, 'topic', false, true, false);

        // 2) Se for usar DLX, garanta o DLX (direct) e a DLQ
        if ($withDlx) {
            $ch->exchange_declare($dlxName, 'direct', false, true, false);
            // DLQ (durável, sem TTL)
            $ch->queue_declare($dlq, false, true, false, false);
            $ch->queue_bind($dlq, $dlxName, $dlq);
        }

        // 3) Declarar a fila principal:
        //    - Primeiro PASSIVE (não altera args, falha se não existir)
        //    - Se 404 NOT_FOUND, crie com os args corretos (DLX etc.)
        try {
            $ch->queue_declare($queue, true /* passive */, true, false, false);
            $this->info("Queue exists (passive ok): {$queue}");
        } catch (AMQPProtocolChannelException $e) {
            if ($e->amqp_reply_code !== 404) {
                throw $e; // outro erro real
            }
            // Não existe -> criar
            $args = [];
            if ($withDlx) {
                $args = [
                    'x-dead-letter-exchange' => $dlxName,
                    'x-dead-letter-routing-key' => $dlq,
                ];
            }
            $ch->queue_declare($queue, false, true, false, false, false, new AMQPTable($args));
            $this->info("Queue created: {$queue} (args: ".json_encode($args).')');
        }

        // 4) Retry (opcional): TTL + volta pela default exchange para a principal
        if ($withRetry) {
            $args = [
                'x-message-ttl' => $retryTtl,
                'x-dead-letter-exchange' => '',       // default exchange
                'x-dead-letter-routing-key' => $queue,   // volta pra principal
            ];
            $ch->queue_declare($retryQueue, false, true, false, false, false, new AMQPTable($args));
            $this->info("Retry queue created: {$retryQueue} (TTL={$retryTtl} ms)");
        }

        // 5) Binding principal (routing key exata)
        $ch->queue_bind($queue, $exchange, $rk);

        $ch->close();
        $conn->close();

        $this->info("OK: exchange={$exchange}, queue={$queue}, rk={$rk}"
            .($withDlx ? ", dlx={$dlxName}, dlq={$dlq}" : '')
            .($withRetry ? ", retry={$retryQueue}" : '')
        );

        return self::SUCCESS;
    }
}
