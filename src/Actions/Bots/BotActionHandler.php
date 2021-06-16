<?php

namespace RTippin\Messenger\Actions\Bots;

use RTippin\Messenger\Contracts\ActionHandler;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;

abstract class BotActionHandler implements ActionHandler
{
    /**
     * @var BotAction|null
     */
    protected ?BotAction $action = null;

    /**
     * @var Message|null
     */
    protected ?Message $message = null;

    /**
     * @var string|null
     */
    protected ?string $matchingTrigger = null;

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
        $payload = json_decode($this->action->payload, true);

        if (! is_null($payload) && ! is_null($key)) {
            return $payload[$key];
        }

        return $payload;
    }

    /**
     * @inheritDoc
     */
    public function setAction(BotAction $action): self
    {
        $this->action = $action;

        Messenger::setProvider($action->bot);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setMessage(Message $message, string $matchingTrigger): self
    {
        $this->message = $message;

        $this->matchingTrigger = $matchingTrigger;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function startCooldown(): self
    {
        if ($this->action->bot->cooldown > 0) {
            $this->action->bot->startCooldown();
        }

        if ($this->action->cooldown > 0) {
            $this->action->startCooldown();
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function releaseCooldown(): self
    {
        $this->action->bot->releaseCooldown();

        $this->action->releaseCooldown();

        return $this;
    }
}
