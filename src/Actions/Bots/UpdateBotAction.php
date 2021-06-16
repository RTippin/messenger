<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\BotActionUpdatedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Resources\BotActionResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\BotAction;

class UpdateBotAction extends BaseMessengerAction
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
     * UpdateBotAction constructor.
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
     * @var array[1]
     * @return $this
     * @see MessengerBots::generateHandlerData()
     * @throws FeatureDisabledException
     */
    public function execute(...$parameters): self
    {
        $this->isBotsEnabled();

        $this->setBotAction($parameters[0])
            ->updateBotAction($parameters[1])
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
    private function updateBotAction(array $params): self
    {
        $this->getBotAction()->update([
            'enabled' => $params['enabled'],
            'cooldown' => $params['cooldown'],
            'triggers' => $params['triggers'],
            'admin_only' => $params['admin_only'],
            'match' => $params['match'],
            'payload' => $params['payload'],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new BotActionResource(
            $this->getBotAction()
        ));

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new BotActionUpdatedEvent(
                $this->messenger->getProvider(true),
                $this->getBotAction(true)
            ));
        }
    }
}
