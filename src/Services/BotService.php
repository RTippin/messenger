<?php

namespace RTippin\Messenger\Services;

use Illuminate\Support\Str;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Traits\ChecksReflection;

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
     * @param Message $message
     * @throws BotException
     */
    public function handleMessage(Message $message): void
    {
        $actions = BotAction::validHandler()->fromThread($message->thread_id)->get();

        foreach ($actions as $action) {
            if ($this->matches($action->match_method, $action->triggers, $message->body)) {
                $this->executeMessage($action, $message);

                return;
            }
        }
    }

    /**
     * @param string $matchMethod
     * @param string $triggers
     * @param string $message
     * @return bool
     */
    public function matches(string $matchMethod, string $triggers, string $message): bool
    {
        foreach (explode('|', $triggers) as $trigger) {
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
     * Check if we should execute the action. Set the cooldown if we do execute.
     *
     * @param BotAction $action
     * @param Message $message
     * @throws BotException
     */
    private function executeMessage(BotAction $action, Message $message): void
    {
        if ($this->shouldExecute($action, $message)) {
            $this->setCooldown($action);

            $this->bots
                ->initializeHandler($action->handler)
                ->setAction($action)
                ->setMessage($message, $this->matchingTrigger)
                ->handle();
        }
    }

    /**
     * Check the handler class exists and implements our interface. It must also
     * not have an active cooldown. If the action has the admin_only flag, check
     * the message owner is a thread admin.
     *
     * @param BotAction $action
     * @param Message $message
     * @return bool
     */
    private function shouldExecute(BotAction $action, Message $message): bool
    {
        return $this->bots->isValidHandler($action->handler)
            && ! $this->hasCooldown($action)
            && $this->hasPermissionToTrigger($action, $message);
    }

    /**
     * @param BotAction $action
     * @param Message $message
     * @return bool
     */
    private function hasPermissionToTrigger(BotAction $action, Message $message): bool
    {
        if ($action->admin_only) {
            return Participant::admins()
                ->forProviderWithModel($message)
                ->where('thread_id', '=', $message->thread_id)
                ->exists();
        }

        return true;
    }

    /**
     * @param BotAction $action
     * @return bool
     */
    private function hasCooldown(BotAction $action): bool
    {
        return $action->hasCooldown() || $action->bot->hasCooldown();
    }

    /**
     * @param BotAction $action
     */
    private function setCooldown(BotAction $action): void
    {
        if ($action->bot->cooldown > 0) {
            $action->bot->setCooldown();
        }

        if ($action->cooldown > 0) {
            $action->setCooldown();
        }
    }
}
