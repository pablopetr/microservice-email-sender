<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process as SymfonyProcess;

class RabbitConsumeDefaults extends Command
{
    protected $signature = 'rabbit:consume:defaults';

    protected $description = 'Run two consumers for transfers and accounts queues (hardcoded).';

    private const Q_TRANSFERS = 'dev.ledger.payments.transfers';

    private const Q_ACCOUNTS = 'dev.ledger.accounts.users';

    private array $procs = [];

    public function handle(): int
    {
        $php = PHP_BINARY;
        $artisan = base_path('artisan');

        $cmds = [
            'transfers' => [$php, $artisan, 'ledger:consume', self::Q_TRANSFERS],
            'accounts' => [$php, $artisan, 'ledger:consume', self::Q_ACCOUNTS],
        ];

        $this->info('Starting consumers:');
        foreach ($cmds as $name => $cmd) {
            $proc = new SymfonyProcess($cmd, base_path());
            $proc->start(function ($type, $buffer) use ($name) {
                $this->output->write('['.strtoupper($name).'] '.$buffer);
            });
            $this->procs[$name] = $proc;
            $this->line(sprintf('  - %s (pid=%s) -> %s', $name, $proc->getPid(), implode(' ', $cmd)));
        }

        $this->line('Consumers running. Press Ctrl+C to stop.');

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, fn () => $this->stopChildren());
            pcntl_signal(SIGTERM, fn () => $this->stopChildren());
        }

        while (true) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            foreach ($this->procs as $name => $proc) {
                if (! $proc->isRunning()) {
                    $exit = $proc->getExitCode();
                    $this->error('['.strtoupper($name)."] exited with code {$exit}");
                    $this->stopChildren();

                    return $exit ?? 1;
                }
            }
            usleep(200000);
        }
    }

    private function stopChildren(): void
    {
        foreach ($this->procs as $proc) {
            if ($proc->isRunning()) {
                if (method_exists($proc, 'signal')) {
                    $proc->signal(SIGTERM);
                    $proc->wait(3);
                    if ($proc->isRunning()) {
                        $proc->signal(SIGKILL);
                    }
                } else {
                    $proc->stop(3, SIGTERM);
                }
            }
        }
        $this->line('All consumers stopped.');
        exit(0);
    }
}
