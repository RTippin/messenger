<?php

namespace RTippin\Messenger\Jobs;

use RTippin\Messenger\Actions\Bots\InstallPackagedBot;
use RTippin\Messenger\Events\InstallPackagedBotEvent;

class ProcessPackagedBotInstall extends BaseMessengerJob
{
    /**
     * @var InstallPackagedBotEvent
     */
    public InstallPackagedBotEvent $event;

    /**
     * Create a new job instance.
     */
    public function __construct(InstallPackagedBotEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @param  InstallPackagedBot  $installer
     * @return void
     */
    public function handle(InstallPackagedBot $installer): void
    {
        $installer->execute(
            $this->event->thread,
            $this->event->provider,
            $this->event->package
        );
    }
}
