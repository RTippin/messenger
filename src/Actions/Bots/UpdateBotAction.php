<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
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
     * @param  ResolvedBotHandlerDTO  $resolved
     * @return $this
     *
     * @throws FeatureDisabledException
     */
    public function execute(BotAction $action, ResolvedBotHandlerDTO $resolved): self
    {
        $this->bailIfDisabled();

        $this->setBotAction($action)
            ->updateBotAction($resolved)
            ->generateResource();

        if ($this->getBotAction()->wasChanged()) {
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
     * @param  ResolvedBotHandlerDTO  $resolved
     * @return $this
     */
    private function updateBotAction(ResolvedBotHandlerDTO $resolved): self
    {
        $this->getBotAction()->update([
            'enabled' => $resolved->enabled,
            'cooldown' => $resolved->cooldown,
            'triggers' => $resolved->triggers,
            'admin_only' => $resolved->adminOnly,
            'match' => $resolved->matchMethod,
            'payload' => $resolved->payload,
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    private function clearActionsCache(): self
    {
        BotAction::clearActionsCacheForThread($this->getBotAction()->bot->thread_id);

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
