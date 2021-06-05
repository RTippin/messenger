<?php

namespace RTippin\Messenger\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
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
        $actions = Action::whereHas('bot', function (Builder $query) use ($message) {
            return $query->where('thread_id', '=', $message->thread_id)
                ->where('enabled', '=', true);
        })->get();

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
        $message = $this->prepare($message);

        switch ($action->match_method) {
            case 'contains': return $this->matchContains($action->trigger, $message);
            case 'exact': return $this->matchExact($action->trigger, $message);
            case 'starts-with': return $this->matchStartsWith($action->trigger, $message);
            default: return false;
        }
    }

    /**
     * @param string $body
     * @return string
     */
    private function prepare(string $body): string
    {
        return trim(Str::lower($body));
    }

    /**
     * @param string $trigger
     * @param string $message
     * @return bool
     */
    private function matchExact(string $trigger, string $message): bool
    {
        return $message === $trigger;
    }

    /**
     * @param string $trigger
     * @param string $message
     * @return bool
     */
    private function matchContains(string $trigger, string $message): bool
    {
        return Str::contains($message, $trigger.' ')
            || Str::endsWith($message, $trigger)
            || $this->matchExact($trigger, $message);
    }

    /**
     * @param string $trigger
     * @param string $message
     * @return bool
     */
    private function matchStartsWith(string $trigger, string $message): bool
    {
        return Str::startsWith($message, $trigger)
            && $this->matchContains($trigger, $message);
    }

    /**
     * @param Action $action
     * @param Message $message
     */
    private function execute(Action $action, Message $message): void
    {
        if (class_exists($action->handler)
            && $this->checkImplementsInterface($action->handler, BotHandler::class)) {
            app($action->handler)->execute($action, $message);
        }
    }
}
