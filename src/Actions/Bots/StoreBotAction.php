<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\NewBotActionEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Bot;

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
     * @param mixed ...$parameters
     * @var Bot[0]
     * @var array[1]
     * @throws FeatureDisabledException
     */
    public function execute(...$parameters): self
    {
        $this->checkCanAddBotAction();

        $this->setBot($parameters[0])
            ->storeBotAction($parameters[1])
            ->generateResource()
            ->fireEvents();

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function checkCanAddBotAction(): void
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
        $this->setBotAction(
            $this->getBot()->actions()->create([
                'owner_id' => $this->messenger->getProvider()->getKey(),
                'owner_type' => $this->messenger->getProvider()->getMorphClass(),
                'handler' => $params['handler'],
                'enabled' => $params['enabled'],
                'cooldown' => $params['cooldown'],
                'triggers' => $params['triggers'],
                'admin_only' => $params['admin_only'],
                'match' => $params['match'],
                'payload' => $params['payload'],
            ])
                ->setRelations([
                    'owner' => $this->messenger->getProvider(),
                    'bot' => $this->getBot(),
                ])
        );

        return $this;
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource($this->getBotAction());

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new NewBotActionEvent(
                $this->getBotAction(true)
            ));
        }
    }
}
