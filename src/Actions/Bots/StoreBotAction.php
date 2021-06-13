<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\NewBotActionEvent;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;

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
     * @var Thread[0]
     * @var Bot[1]
     * @var array[2]
     * @see MessengerBots::generateHandlerData()
     * @throws FeatureDisabledException|BotException
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])->setBot($parameters[1]);

        $this->checkCanAddBotAction($parameters[2]);

        $this->storeBotAction($parameters[2])
            ->generateResource()
            ->fireEvents();

        return $this;
    }

    /**
     * @throws FeatureDisabledException|BotException
     */
    private function checkCanAddBotAction(array $params): void
    {
        if (! $this->messenger->isBotsEnabled()) {
            throw new FeatureDisabledException('Bots are currently disabled.');
        }

        if ($params['unique'] && $this->botHandlerExists($params['handler'])) {
            throw new BotException("You may only have one ({$params['name']}) in a thread at a time.");
        }
    }

    /**
     * @param string $handler
     * @return bool
     */
    private function botHandlerExists(string $handler): bool
    {
        return $this->getThread()
            ->bots()
            ->hasActionWithHandler($handler)
            ->exists();
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
     * TODO
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
