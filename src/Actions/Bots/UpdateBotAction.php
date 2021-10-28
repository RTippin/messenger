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
     * @param  Messenger  $messenger
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(Messenger $messenger, Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
    }

    /**
     * @param  BotAction  $action
     * @param  array  $params
     * @return $this
     *
     * @see MessengerBots::generateHandlerData()
     *
     * @throws FeatureDisabledException
     */
    public function execute(BotAction $action, array $params): self
    {
        $this->bailWhenFeatureDisabled();

        $this->setBotAction($action)
            ->updateBotAction($params)
            ->generateResource();

        if ($this->getBotAction()->wasChanged()) {
            $this->clearActionsCache()->fireEvents();
        }

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
     * @param  array  $params
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
    private function clearActionsCache(): self
    {
        BotAction::clearValidCacheForThread($this->getBotAction()->bot->thread_id);

        return $this;
    }

    /**
     * @return void
     */
    private function generateResource(): void
    {
        $this->setJsonResource(new BotActionResource(
            $this->getBotAction()
        ));
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
