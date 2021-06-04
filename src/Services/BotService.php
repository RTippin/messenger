<?php

namespace RTippin\Messenger\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use RTippin\Messenger\Models\Action;
use RTippin\Messenger\Models\Message;

class BotService
{
    /**
     * @param Message $message
     */
    public function handle(Message $message): void
    {
        $actions = Action::whereHas('bot', function (Builder $query) use ($message) {
            return $query->where('thread_id', '=', $message->thread_id)
                ->where('enabled', '=', true);
        })->get();

        $body = $this->prepare($message->body);

        foreach ($actions as $action) {
            if ($this->matches($action, $body)) {
                $this->execute($action, $message);

                return;
            }
        }
    }

    /**
     * @param string $body
     * @return string
     */
    public function prepare(string $body): string
    {
        return trim(Str::lower($body));
    }

    /**
     * @param Action $action
     * @param string $message
     * @return bool
     */
    public function matches(Action $action, string $message): bool
    {
        if ($action->exact_match) {
            return $message === $action->trigger;
        }

        return Str::startsWith($message, $action->trigger)
            && (Str::contains($message, $action->trigger.' ')
                || $message === $action->trigger);
    }

    /**
     * @param Action $action
     * @param Message $message
     */
    private function execute(Action $action, Message $message): void
    {
        if (class_exists($action->handler)) {
            app($action->handler)->execute($action, $message);
        }
    }
}
