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
     * @var Bot[0]
     * @throws FeatureDisabledException|BotException
     */
    public function execute(...$parameters): self
    {
        $this->checkCanAddBotAction();

        $this->setBot($parameters[0]);

        return $this;
    }

    /**
     * @throws FeatureDisabledException|BotException
     */
    private function checkCanAddBotAction(): void
    {
        if (! $this->messenger->isBotsEnabled()) {
            throw new FeatureDisabledException('Bots are currently disabled.');
        }

        if (! $this->bots->isActiveHandlerSet()) {
            throw new BotException('No bot handler has been set.');
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
                'enabled' => $params['enabled'],
                'cooldown' => $params['cooldown'],
                'triggers' => $params['triggers'],
                'admin_only' => $params['admin_only'],
                'match_method' => $params['match_method'],
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
