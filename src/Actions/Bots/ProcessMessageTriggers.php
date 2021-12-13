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
     * @param  Messenger  $messenger
     * @param  MessengerBots  $bots
     * @param  BotMatchingService  $matcher
     * @param  Dispatcher  $dispatcher
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
     * @param  Thread  $thread
     * @param  Message  $message
     * @param  bool  $isGroupAdmin
     * @param  null|string  $senderIp
     * @return $this
     *
     * @throws FeatureDisabledException
     */
    public function execute(Thread $thread,
                            Message $message,
                            bool $isGroupAdmin = false,
                            ?string $senderIp = null): self
    {
        $this->bailIfDisabled();

        $this->isGroupAdmin = $isGroupAdmin;
        $this->senderIp = $senderIp;
        $this->botsTriggered = Collection::make();

        $this->setThread($thread)->setMessage($message);

        BotAction::getActionsWithBotFromThread($this->getThread()->id)->each(
            fn (BotAction $action) => $this->matchAndHandleAction($action)
        );

        $this->startTriggeredBotCooldowns();

        return $this;
    }

    /**
     * If using MATCH_ANY, handle the action immediately, otherwise loop
     * through the actions triggers, executing the handle method upon
     * a successful match.
     *
     * @param  BotAction  $action
     * @return void
     */
    private function matchAndHandleAction(BotAction $action): void
    {
        if ($action->getMatchMethod() === MessengerBots::MATCH_ANY) {
            $this->handleAction($action);

            return;
        }

        foreach ($action->getTriggers() as $trigger) {
            if ($this->matcher->matches(
                $action->getMatchMethod(),
                $trigger,
                $this->getMessage()->body
            )) {
                $this->handleAction($action, $trigger);
                break;
            }
        }
    }

    /**
     * Check if we should execute the action's handler. When executing,
     * set the proper data into the handler, and start the action's
     * cooldown, if any. Fire events when the action is handled
     * or failed.
     *
     * @param  BotAction  $action
     * @param  string|null  $trigger
     * @return void
     */
    private function handleAction(BotAction $action, ?string $trigger = null): void
    {
        if (! $this->shouldExecute($action)) {
            return;
        }

        $this->botActionStarting($action);

        try {
            $this->bots
                ->initializeHandler($action->handler)
                ->setDataForHandler(
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
     * Check the action's handler is valid and that the action has no
     * active cooldowns. If the action has the admin_only flag,
     * the group admin flag must also be true.
     *
     * @param  BotAction  $action
     * @return bool
     */
    private function shouldExecute(BotAction $action): bool
    {
        return $action->notOnAnyCooldown()
            && $this->hasPermissionToTrigger($action);
    }

    /**
     * @param  BotAction  $action
     * @return bool
     */
    private function hasPermissionToTrigger(BotAction $action): bool
    {
        return $action->admin_only
            ? $this->isGroupAdmin
            : true;
    }

    /**
     * Flush the Bots service, removing any prior initialized handler.
     * Set the bot being triggered, and start the action cooldown.
     *
     * @param  BotAction  $action
     * @return void
     */
    private function botActionStarting(BotAction $action): void
    {
        $this->bots->flush();
        $this->botsTriggered->push($action->bot);
        $action->startCooldown();
    }

    /**
     * After the action completes, check if the handler wants to release
     * the cooldown. Then flush the Bots service, removing any the
     * initialized handler.
     *
     * @param  BotAction  $action
     * @return void
     */
    private function botActionEnding(BotAction $action): void
    {
        if ($this->bots->isActiveHandlerSet()
            && $this->bots->getActiveHandler()->shouldReleaseCooldown()) {
            $action->releaseCooldown();
        }

        $this->bots->flush();
    }

    /**
     * One all matching actions have been handled, we set any
     * bot cooldowns from used actions.
     *
     * @return void
     */
    private function startTriggeredBotCooldowns(): void
    {
        $this->botsTriggered
            ->unique('id')
            ->each(fn (Bot $bot) => $bot->startCooldown());
    }

    /**
     * @param  BotAction  $action
     * @param  string|null  $trigger
     * @return void
     */
    private function fireHandledEvent(BotAction $action, ?string $trigger): void
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
     * @param  BotAction  $action
     * @param  Throwable  $exception
     * @return void
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
