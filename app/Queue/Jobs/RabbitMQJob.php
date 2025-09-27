<?php

namespace App\Queue\Jobs;

use App\Handlers\TransferCompletedHandler;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob as BaseJob;

class RabbitMQJob extends BaseJob
{
    public function fire(): void
    {
        $raw = $this->getRawBody();

        (new TransferCompletedHandler)->handle($raw);

        logger()->alert($raw);

        $this->delete();
    }

    public function getName(): string { return 'rabbitmq.raw'; }
}
