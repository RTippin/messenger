<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\NewBotEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Request\BotRequest;
use RTippin\Messenger\Http\Resources\BotResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;

class StoreBot extends BaseMessengerAction
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
     * StoreBot constructor.
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
     * Create a new thread bot!
     *
     * @param  Thread  $thread
     * @param  array  $params
     * @return $this
     *
     * @see BotRequest
     *
     * @throws FeatureDisabledException
     */
    public function execute(Thread $thread, array $params): self
    {
        $this->bailIfDisabled();

        $this->setThread($thread)
            ->storeBot($params)
            ->clearActionsCache()
            ->generateResource()
            ->fireEvents();

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function bailIfDisabled(): void
    {
        if (! $this->messenger->isBotsEnabled()) {
            throw new FeatureDisabledException('Bots are currently disabled.');
        }
    }

    /**
     * @param  array  $params
     * @return $this
     */
    private function storeBot(array $params): self
    {
        $this->setBot(
            $this->getThread()->bots()->create([
                'owner_id' => $this->messenger->getProvider()->getKey(),
                'owner_type' => $this->messenger->getProvider()->getMorphClass(),
                'enabled' => $params['enabled'],
                'name' => $params['name'],
                'cooldown' => $params['cooldown'],
                'hide_actions' => $params['hide_actions'],
                'avatar' => null,
            ])
                ->setRelations([
                    'owner' => $this->messenger->getProvider(),
                    'thread' => $this->getThread(),
                ])
        );

        return $this;
    }

    /**
     * @return $this
     */
    private function clearActionsCache(): self
    {
        if ($this->shouldExecuteChains()) {
            BotAction::clearActionsCacheForThread($this->getThread()->id);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new BotResource(
            $this->getBot()
        ));

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new NewBotEvent(
                $this->getBot()
            ));
        }
    }
}
