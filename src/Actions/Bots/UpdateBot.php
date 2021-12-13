<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\BotUpdatedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Request\BotRequest;
use RTippin\Messenger\Http\Resources\BotResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;

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
     * @var string
     */
    private string $originalName;

    /**
     * UpdateBot constructor.
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
     * @param  Bot  $bot
     * @param  array  $params
     * @return $this
     *
     * @see BotRequest
     *
     * @throws FeatureDisabledException
     */
    public function execute(Bot $bot, array $params): self
    {
        $this->bailIfDisabled();

        $this->setBot($bot);

        $this->originalName = $this->getBot()->name;

        $this->updateBot($params)->generateResource();

        if ($this->getBot()->wasChanged()) {
            $this->clearActionsCache()->fireEvents();
        }

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
    private function updateBot(array $params): self
    {
        $this->getBot()->update($params);

        return $this;
    }

    /**
     * @return $this
     */
    private function clearActionsCache(): self
    {
        BotAction::clearActionsCacheForThread($this->getBot()->thread_id);

        return $this;
    }

    /**
     * @return void
     */
    private function generateResource(): void
    {
        $this->setJsonResource(new BotResource(
            $this->getBot()
        ));
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new BotUpdatedEvent(
                $this->messenger->getProvider(true),
                $this->getBot(true),
                $this->originalName
            ));
        }
    }
}
