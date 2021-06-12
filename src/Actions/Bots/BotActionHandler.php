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
     * @var array|null
     */
    protected ?array $payload = null;

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
    public function serializePayload(): ?string
    {
        return is_null($this->payload)
            ? null
            : json_encode($this->payload);
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
}
