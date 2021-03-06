<?php

namespace RTippin\Messenger\Actions\Bots;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\BotArchivedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Bot;

class ArchiveBot extends BaseMessengerAction
{
    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * ArchiveBot constructor.
     *
     * @param Messenger $messenger
     * @param Dispatcher $dispatcher
     */
    public function __construct(Messenger $messenger, Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
    }

    /**
     * @param mixed ...$parameters
     * @var Bot[0]
     * @return $this
     * @throws Exception|FeatureDisabledException
     */
    public function execute(...$parameters): self
    {
        $this->isBotsEnabled();

        $this->setBot($parameters[0])
            ->archiveBot()
            ->fireEvents();

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function isBotsEnabled(): void
    {
        if (! $this->messenger->isBotsEnabled()) {
            throw new FeatureDisabledException('Bots are currently disabled.');
        }
    }

    /**
     * @return $this
     * @throws Exception
     */
    private function archiveBot(): self
    {
        $this->getBot()->delete();

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new BotArchivedEvent(
                $this->messenger->getProvider(true),
                $this->getBot(true)
            ));
        }
    }
}
