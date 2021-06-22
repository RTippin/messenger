<?php

namespace RTippin\Messenger\Actions\Bots;

use RTippin\Messenger\Contracts\ActionHandler;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;

abstract class BotActionHandler implements ActionHandler
{
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
    public function authorize(): bool
    {
        return true;
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
    public function setAction(BotAction $action): self
    {
        $this->action = $action;

        Messenger::setProvider($action->bot);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setThread(Thread $thread): self
    {
        $this->thread = $thread;

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
