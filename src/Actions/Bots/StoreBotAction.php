<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
use RTippin\Messenger\Events\NewBotActionEvent;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Resources\BotActionResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
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
     * @param  Messenger  $messenger
     * @param  MessengerBots  $bots
     * @param  Dispatcher  $dispatcher
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
     * @param  Thread  $thread
     * @param  Bot  $bot
     * @param  ResolvedBotHandlerDTO  $resolved
     * @return $this
     *
     * @throws FeatureDisabledException|BotException
     */
    public function execute(Thread $thread,
                            Bot $bot,
                            ResolvedBotHandlerDTO $resolved): self
    {
        $this->setThread($thread)->setBot($bot);

        $this->bailIfCanAddBotActionFails($resolved);

        $this->storeBotAction($resolved)
            ->clearActionsCache()
            ->generateResource()
            ->fireEvents();

        return $this;
    }

    /**
     * @param  ResolvedBotHandlerDTO  $resolved
     * @throws BotException
     * @throws FeatureDisabledException
     */
    private function bailIfCanAddBotActionFails(ResolvedBotHandlerDTO $resolved): void
    {
        if (! $this->messenger->isBotsEnabled()) {
            throw new FeatureDisabledException('Bots are currently disabled.');
        }

        if ($resolved->handlerDTO->unique
            && $this->botHasHandler($resolved->handlerDTO->class)) {
            throw new BotException("You may only have one ({$resolved->handlerDTO->name}) on {$this->getBot()->name} at a time.");
        }

        if ($resolved->handlerDTO->shouldAuthorize
            && ! $this->authorizeHandler($resolved->handlerDTO->class)) {
            throw new BotException("Not authorized to add ({$resolved->handlerDTO->name}) to {$this->getBot()->name}.");
        }
    }

    /**
     * @param  string  $handler
     * @return bool
     *
     * @throws BotException
     */
    private function authorizeHandler(string $handler): bool
    {
        return $this->bots->initializeHandler($handler)->authorize();
    }

    /**
     * @param  string  $handler
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
     * @param  ResolvedBotHandlerDTO  $resolved
     * @return $this
     */
    private function storeBotAction(ResolvedBotHandlerDTO $resolved): self
    {
        $this->setBotAction(
            $this->getBot()->actions()->create([
                'owner_id' => $this->messenger->getProvider()->getKey(),
                'owner_type' => $this->messenger->getProvider()->getMorphClass(),
                'handler' => $resolved->handlerDTO->class,
                'enabled' => $resolved->enabled,
                'cooldown' => $resolved->cooldown,
                'triggers' => $resolved->triggers,
                'admin_only' => $resolved->adminOnly,
                'match' => $resolved->matchMethod,
                'payload' => $resolved->payload,
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
    private function clearActionsCache(): self
    {
        BotAction::clearActionsCacheForThread($this->getThread()->id);

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
