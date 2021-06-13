<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Messenger;

class StoreBotAction extends BaseMessengerAction
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
     * StoreBotAction constructor.
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
     * //TODO
     *
     * @param mixed ...$parameters
     */
    public function execute(...$parameters): self
    {
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
     * @param array $params
     * @return $this
     */
    private function storeBotAction(array $params): self
    {
        return $this;
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
//        if ($this->shouldFireEvents()) {
//            $this->dispatcher->dispatch();
//        }
    }
}
