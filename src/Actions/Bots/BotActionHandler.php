<?php

namespace RTippin\Messenger\Actions\Bots;

use RTippin\Messenger\Contracts\ActionHandler;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\MessengerComposer;

/**
 * To authorize the end user add the action handler to a bot, you must define the
 * 'authorize()' method and return bool. If unauthorized, it will also hide the
 * handler from appearing in the available handlers list when adding actions to
 * a bot. Return true if no authorization is needed. This does NOT authorize
 * being triggered once added to a bot action.
 *
 * @method bool authorize()
 */
abstract class BotActionHandler implements ActionHandler
{
    /**
     * @var Bot|null
     */
    protected ?Bot $bot = null;

    /**
     * @var BotAction|null
     */
    protected ?BotAction $action = null;

    /**
     * @var Thread|null
     */
    protected ?Thread $thread = null;

    /**
     * @var Message|null
     */
    protected ?Message $message = null;

    /**
     * @var string|null
     */
    protected ?string $matchingTrigger = null;

    /**
     * @var string|null
     */
    protected ?string $senderIp = null;

    /**
     * @var bool
     */
    protected bool $shouldReleaseCooldown = false;

    /**
     * @inheritDoc
     */
    abstract public static function getSettings(): array;

    /**
     * @inheritDoc
     */
    abstract public function handle(): void;

    /**
     * @inheritDoc
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function errorMessages(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function serializePayload(?array $payload): ?string
    {
        return is_null($payload)
            ? null
            : json_encode($payload);
    }

    /**
     * @inheritDoc
     */
    public function getPayload(?string $key = null)
    {
        return $this->action->getPayload($key);
    }

    /**
     * @inheritDoc
     */
    public function setDataForMessage(Thread $thread,
                                      BotAction $action,
                                      Message $message,
                                      ?string $matchingTrigger,
                                      ?string $senderIp): self
    {
        $this->thread = $thread;
        $this->action = $action;
        $this->bot = $action->bot;
        $this->message = $message;
        $this->matchingTrigger = $matchingTrigger;
        $this->senderIp = $senderIp;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function composer(): MessengerComposer
    {
        return app(MessengerComposer::class)
            ->to($this->thread)
            ->from($this->bot);
    }

    /**
     * @inheritDoc
     */
    public function releaseCooldown(): void
    {
        $this->shouldReleaseCooldown = true;
    }

    /**
     * @inheritDoc
     */
    public function shouldReleaseCooldown(): bool
    {
        return $this->shouldReleaseCooldown;
    }
}
