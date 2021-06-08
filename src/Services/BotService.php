<?php

namespace RTippin\Messenger\Services;

use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Contracts\BotHandler;
use RTippin\Messenger\Models\Action;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Traits\ChecksReflection;

class BotService
{
    use ChecksReflection;

    /**
     * @param Message $message
     */
    public function handle(Message $message): void
    {
        $actions = Action::validHandler()->fromThread($message->thread_id)->get();

        foreach ($actions as $action) {
            if ($this->matches($action, $message->body)) {
                $this->execute($action, $message);

                return;
            }
        }
    }

    /**
     * @param Action $action
     * @param string $message
     * @return bool
     */
    public function matches(Action $action, string $message): bool
    {
        switch ($action->match_method) {
            case 'contains': return $this->matchContains($action->trigger, $message);
            case 'contains:caseless': return $this->matchContains($action->trigger, $message, true);
            case 'contains:any': return $this->matchContainsAny($action->trigger, $message);
            case 'contains:any:caseless': return $this->matchContainsAny($action->trigger, $message, true);
            case 'exact': return $this->matchExact($action->trigger, $message);
            case 'exact:caseless': return $this->matchExact($action->trigger, $message, true);
            case 'starts:with': return $this->matchStartsWith($action->trigger, $message);
            case 'starts:with:caseless': return $this->matchStartsWith($action->trigger, $message, true);
            default: return false;
        }
    }

    /**
     * @param string $trigger
     * @param string $message
     * @param bool $caseless
     * @return bool
     */
    public function matchExact(string $trigger, string $message, bool $caseless = false): bool
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
    public function matchContains(string $trigger, string $message, bool $caseless = false): bool
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
    public function matchContainsAny(string $trigger, string $message, bool $caseless = false): bool
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
    public function matchStartsWith(string $trigger, string $message, bool $caseless = false): bool
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
     * @param Action $action
     * @param Message $message
     */
    private function execute(Action $action, Message $message): void
    {
        if (class_exists($action->handler)
            && $this->checkImplementsInterface($action->handler, BotHandler::class)
            && ! $this->hasCooldown($action)) {
            $this->setCooldown($action);

            app($action->handler)->execute($action, $message);
        }
    }

    /**
     * @param Action $action
     * @return bool
     */
    private function hasCooldown(Action $action): bool
    {
        return $action->hasCooldown() || $action->bot->hasCooldown();
    }

    /**
     * @param Action $action
     */
    private function setCooldown(Action $action): void
    {
        if ($action->bot->cooldown > 0) {
            $action->bot->setCooldown();
        }

        if ($action->cooldown > 0) {
            $action->setCooldown();
        }
    }
}
