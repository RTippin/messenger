<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Messenger;

class CallsUpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:calls:up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Put the call system back online if it is temporarily disabled.';

    /**
     * Execute the console command.
     *
     * @param Messenger $messenger
     * @return void
     * @throws InvalidArgumentException
     */
    public function handle(Messenger $messenger): void
    {
        if (! $messenger->isCallingEnabled()) {
            $this->info('Call system currently disabled.');
        } elseif (! $messenger->isCallingTemporarilyDisabled()) {
            $this->info('Call system is already online.');
        } else {
            $messenger->removeTemporaryCallShutdown();

            $this->info('Call system is now online.');
        }
    }
}
