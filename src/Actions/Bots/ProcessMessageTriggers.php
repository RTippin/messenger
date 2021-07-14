<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\BotActionFailedEvent;
use RTippin\Messenger\Events\BotActionHandledEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\BotMatchingService;
use Throwable;

class ProcessMessageTriggers extends BaseMessengerAction
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
     * @var BotMatchingService
     */
    private BotMatchingService $matcher;

    /**
     * @var bool
     */
    private bool $isGroupAdmin;

    /**
     * @var string|null
     */
    private ?string $senderIp;

    /**
     * @var Collection
     */
    private Collection $botsTriggered;

    /**
     * ProcessMessageTriggers constructor.
     *
     * @param Messenger $messenger
     * @param MessengerBots $bots
     * @param BotMatchingService $matcher
     * @param Dispatcher $dispatcher
     */
    public function __construct(Messenger $messenger,
                                MessengerBots $bots,
                                BotMatchingService $matcher,
                                Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
        $this->bots = $bots;
        $this->matcher = $matcher;
    }

    /**
     * Process all matching actions that the message
     * body matches through the action triggers.
     *
     * @param mixed ...$parameters
     * @param Thread[0]
     * @param Message[1]
     * @param bool[2]
     * @param null|string[3]
     * @return $this
     * @throws FeatureDisabledException
     */
    public function execute(...$parameters): self
    {
        $this->isBotsEnabled();

        $this->isGroupAdmin = $parameters[2];
        $this->senderIp = $parameters[3] ?? null;
        $this->botsTriggered = new Collection([]);

        $this->setThread($parameters[0])
            ->setMessage($parameters[1]);

        foreach ($this->getBotActions() as $action) {
            $this->matchActionTriggers($action);
        }

        $this->startTriggeredBotCooldowns();

        return $this;
    }

    /**
     * Loop through the triggers, and attempt to handle ones that match.
     *
     * @param BotAction $action
     */
    private function matchActionTriggers(BotAction $action): void
    {
        foreach ($action->getTriggers() as $trigger) {
            if ($this->matcher->matches($action->match, $trigger, $this->getMessage()->body)) {
                $this->handleAction($action, $trigger);
            }
        }
    }

    /**
     * Check if we should execute the actions handler. When executing,
     * set the proper data into the handler, and start the actions
     * cooldown, if any. Fire events when the action is handled
     * or failed.
     *
     * @param BotAction $action
     * @param string $trigger
     */
    private function handleAction(BotAction $action, string $trigger): void
    {
        if ($this->shouldExecute($action)) {
            $this->botActionStarting($action);

            try {
                $this->bots
                    ->initializeHandler($action->handler)
                    ->setDataForMessage(
                        $this->getThread(),
                        $action,
                        $this->getMessage(),
                        $trigger,
                        $this->isGroupAdmin,
                        $this->senderIp
                    )
                    ->handle();

                $this->fireHandledEvent($action, $trigger);
            } catch (Throwable $e) {
                $this->fireFailedEvent($action, $e);
            }

            $this->botActionEnding($action);
        }
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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getBotActions(): \Illuminate\Database\Eloquent\Collection
    {
        return BotAction::validFromThread($this->getThread()->id)->with('bot')->get();
    }

    /**
     * Check the actions handler is valid and that the action has no
     * active cooldowns. If the action has the admin_only flag,
     * the group admin flag must also be true.
     *
     * @param BotAction $action
     * @return bool
     */
    private function shouldExecute(BotAction $action): bool
    {
        return $action->notOnAnyCooldown()
            && $this->hasPermissionToTrigger($action);
    }

    /**
     * @param BotAction $action
     * @return bool
     */
    private function hasPermissionToTrigger(BotAction $action): bool
    {
        return $action->admin_only
            ? $this->isGroupAdmin
            : true;
    }

    /**
     * Set the bot being triggered, and start the action cooldown.
     *
     * @param BotAction $action
     */
    private function botActionStarting(BotAction $action): void
    {
        $this->botsTriggered->push($action->bot);

        $action->startCooldown();
    }

    /**
     * After the action completes, check if the handler
     * wants to release the cooldown.
     *
     * @param BotAction $action
     */
    private function botActionEnding(BotAction $action): void
    {
        if ($this->bots->getActiveHandler()->shouldReleaseCooldown()) {
            $action->releaseCooldown();
        }
    }

    /**
     * One all matching actions have been handled, we set any
     * bot cooldowns from used actions.
     */
    private function startTriggeredBotCooldowns(): void
    {
        $this->botsTriggered
            ->unique('id')
            ->each(fn (Bot $bot) => $bot->startCooldown());
    }

    /**
     * @param BotAction $action
     * @param string $trigger
     */
    private function fireHandledEvent(BotAction $action, string $trigger): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new BotActionHandledEvent(
                $action,
                $this->getMessage(true),
                $trigger
            ));
        }
    }

    /**
     * @param BotAction $action
     * @param Throwable $exception
     */
    private function fireFailedEvent(BotAction $action, Throwable $exception): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new BotActionFailedEvent(
                $action,
                $exception
            ));
        }
    }
}
