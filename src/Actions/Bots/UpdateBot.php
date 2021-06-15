<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\BotUpdatedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Request\BotRequest;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Bot;

class UpdateBot extends BaseMessengerAction
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
     * UpdateBot constructor.
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
     * @var BotRequest[1]
     * @return $this
     * @throws FeatureDisabledException
     */
    public function execute(...$parameters): self
    {
        $this->isBotsEnabled();

        $this->setBot($parameters[0])
            ->updateBot($parameters[1])
            ->generateResource()
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
     * @param array $params
     * @return $this
     */
    private function updateBot(array $params): self
    {
        $this->getBot()->update($params);

        return $this;
    }

    /**
     * TODO.
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource($this->getBot());

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new BotUpdatedEvent(
                $this->messenger->getProvider(true),
                $this->getBot(true)
            ));
        }
    }
}
