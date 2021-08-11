<?php

namespace RTippin\Messenger\Actions\Bots;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\BotActionRemovedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\BotAction;

class RemoveBotAction extends BaseMessengerAction
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
     * RemoveBotAction constructor.
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
     * @var BotAction[0]
     * @return $this
     * @throws Exception|FeatureDisabledException
     */
    public function execute(...$parameters): self
    {
        $this->bailWhenFeatureDisabled();

        $this->setBotAction($parameters[0])
            ->destroyBotAction()
            ->fireEvents();

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function bailWhenFeatureDisabled(): void
    {
        if (! $this->messenger->isBotsEnabled()) {
            throw new FeatureDisabledException('Bots are currently disabled.');
        }
    }

    /**
     * @return $this
     * @throws Exception
     */
    private function destroyBotAction(): self
    {
        $this->getBotAction()->delete();

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new BotActionRemovedEvent(
                $this->messenger->getProvider(true),
                $this->getBotAction()->toArray()
            ));
        }
    }
}
