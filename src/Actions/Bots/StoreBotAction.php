<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\NewBotActionEvent;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Resources\BotActionResource;
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
     * @var MessengerBots
     */
    private MessengerBots $bots;

    /**
     * StoreBotAction constructor.
     *
     * @param Messenger $messenger
     * @param MessengerBots $bots
     * @param Dispatcher $dispatcher
     */
    public function __construct(Messenger $messenger,
                                MessengerBots $bots,
                                Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
        $this->bots = $bots;
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

        $this->bailIfCanAddBotActionFails($parameters[2]);

        $this->storeBotAction($parameters[2])
            ->generateResource()
            ->fireEvents();

        return $this;
    }

    /**
     * @throws FeatureDisabledException|BotException
     */
    private function bailIfCanAddBotActionFails(array $params): void
    {
        if (! $this->messenger->isBotsEnabled()) {
            throw new FeatureDisabledException('Bots are currently disabled.');
        }

        if ($params['unique'] && $this->botHasHandler($params['handler'])) {
            throw new BotException("You may only have one ({$params['name']}) on {$this->getBot()->name} at a time.");
        }

        if ($params['authorize'] && ! $this->authorizeHandler($params['handler'])) {
            throw new BotException("Not authorized to add ({$params['name']}) to {$this->getThread()->name()}.");
        }
    }

    /**
     * @param string $handler
     * @return bool
     * @throws BotException
     */
    private function authorizeHandler(string $handler): bool
    {
        return $this->bots->initializeHandler($handler)->authorize();
    }

    /**
     * @param string $handler
     * @return bool
     */
    private function botHasHandler(string $handler): bool
    {
        return $this->getBot()
            ->validActions()
            ->handler($handler)
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
            $this->dispatcher->dispatch(new NewBotActionEvent(
                $this->getBotAction(true)
            ));
        }
    }
}
