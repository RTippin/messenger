<?php

namespace RTippin\Messenger\Services;

use Illuminate\Support\Str;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Traits\ChecksReflection;
use Throwable;

class BotService
{
    use ChecksReflection;

    /**
     * @var MessengerBots
     */
    private MessengerBots $bots;

    /**
     * @var string
     */
    private string $matchingTrigger;

    /**
     * BotService constructor.
     */
    public function __construct(MessengerBots $bots)
    {
        $this->bots = $bots;
    }

    /**
     * Get all valid bot actions from the messages thread. Loop through each
     * action to see if any triggers match the messages body. If a match is
     * found, execute the handler.
     *
     * @param Message $message
     * @param Thread $thread
     * @param bool $isGroupAdmin
     */
    public function handleMessage(Message $message,
                                  Thread $thread,
                                  bool $isGroupAdmin): void
    {
        $actions = BotAction::enabled()
            ->hasEnabledBotFromThread($thread->id)
            ->validHandler()
            ->get();

        foreach ($actions as $action) {
            if ($this->matches($action->match, $action->getTriggers(), $message->body)) {
                $this->executeMessage(
                    $action,
                    $message,
                    $thread,
                    $isGroupAdmin
                );
            }
        }
    }

    /**
     * Using the match method, loop through all triggers
     * and attempt to match it against the message.
     *
     * @param string $matchMethod
     * @param array $triggers
     * @param string $message
     * @return bool
     */
    public function matches(string $matchMethod, array $triggers, string $message): bool
    {
        foreach ($triggers as $trigger) {
            if ($this->doesMatch($matchMethod, $trigger, $message)) {
                $this->matchingTrigger = $trigger;

                return true;
            }
        }

        return false;
    }

    /**
     * @param string $method
     * @param string $trigger
     * @param string $message
     * @return bool
     */
    private function doesMatch(string $method, string $trigger, string $message): bool
    {
        switch ($method) {
            case 'contains': return $this->matchContains($trigger, $message);
            case 'contains:caseless': return $this->matchContains($trigger, $message, true);
            case 'contains:any': return $this->matchContainsAny($trigger, $message);
            case 'contains:any:caseless': return $this->matchContainsAny($trigger, $message, true);
            case 'exact': return $this->matchExact($trigger, $message);
            case 'exact:caseless': return $this->matchExact($trigger, $message, true);
            case 'starts:with': return $this->matchStartsWith($trigger, $message);
            case 'starts:with:caseless': return $this->matchStartsWith($trigger, $message, true);
            default: return false;
        }
    }

    /**
     * The trigger must match the message exactly.
     *
     * @param string $trigger
     * @param string $message
     * @param bool $caseless
     * @return bool
     */
    private function matchExact(string $trigger, string $message, bool $caseless = false): bool
    {
        $trigger = $caseless ? Str::lower($trigger) : $trigger;
        $message = $this->prepareMessage($message, $caseless);

        return $trigger === $message;
    }

    /**
     * The trigger can be anywhere within a message.
     * Cannot be part of or inside another word.
     *
     * @param string $trigger
     * @param string $message
     * @param bool $caseless
     * @return bool
     */
    private function matchContains(string $trigger, string $message, bool $caseless = false): bool
    {
        $trigger = $caseless ? Str::lower($trigger) : $trigger;
        $message = $this->prepareMessage($message, $caseless);

        return (bool) preg_match('/(?<=[\s,.:;"\']|^)'.$trigger.'(?=[\s,.:;"\']|$)/', $message);
    }

    /**
     * The trigger can be anywhere within a message,
     * including inside another word.
     *
     * @param string $trigger
     * @param string $message
     * @param bool $caseless
     * @return bool
     */
    private function matchContainsAny(string $trigger, string $message, bool $caseless = false): bool
    {
        $trigger = $caseless ? Str::lower($trigger) : $trigger;
        $message = $this->prepareMessage($message, $caseless);

        return Str::contains($message, $trigger);
    }

    /**
     * The trigger must be the lead phrase within the message.
     * Cannot be part of or inside another word.
     *
     * @param string $trigger
     * @param string $message
     * @param bool $caseless
     * @return bool
     */
    private function matchStartsWith(string $trigger, string $message, bool $caseless = false): bool
    {
        $trigger = $caseless ? Str::lower($trigger) : $trigger;
        $message = $this->prepareMessage($message, $caseless);

        return Str::startsWith($message, $trigger)
            && $this->matchContains($trigger, $message, $caseless);
    }

    /**
     * @param string $string
     * @param bool $lower
     * @return string
     */
    private function prepareMessage(string $string, bool $lower): string
    {
        return trim($lower ? Str::lower($string) : $string);
    }

    /**
     * Check if we should execute the actions handler. When executing,
     * set the proper data into the handler, and start the actions
     * cooldown, if any.
     *
     * @param BotAction $action
     * @param Message $message
     * @param Thread $thread
     * @param bool $isGroupAdmin
     */
    private function executeMessage(BotAction $action,
                                    Message $message,
                                    Thread $thread,
                                    bool $isGroupAdmin): void
    {
        if ($this->shouldExecute($action, $isGroupAdmin)) {
            try {
                $this->bots
                    ->initializeHandler($action->handler)
                    ->setAction($action)
                    ->setThread($thread)
                    ->setMessage($message, $this->matchingTrigger)
                    ->startCooldown()
                    ->handle();
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Check the actions handler is valid and that the action has no
     * active cooldowns. If the action has the admin_only flag,
     * the group admin flag must also be true.
     *
     * @param BotAction $action
     * @param bool $isGroupAdmin
     * @return bool
     */
    private function shouldExecute(BotAction $action, bool $isGroupAdmin): bool
    {
        return $this->bots->isValidHandler($action->handler)
            && $action->notOnAnyCooldown()
            && $this->hasPermissionToTrigger($action, $isGroupAdmin);
    }

    /**
     * @param BotAction $action
     * @param bool $isGroupAdmin
     * @return bool
     */
    private function hasPermissionToTrigger(BotAction $action, bool $isGroupAdmin): bool
    {
        return $action->admin_only
            ? $isGroupAdmin
            : true;
    }
}
