<?php

namespace RTippin\Messenger\Actions\Bots;

use RTippin\Messenger\Contracts\ActionHandler;
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
     * Set the alias we will use when attaching the handler to
     * a bot model via a form post.
     *
     * @return string
     */
    abstract public static function getAlias(): string;

    /**
     * Set the description of the handler.
     *
     * @return string
     */
    abstract public static function getDescription(): string;

    /**
     * Set the name of the handler we will display to the frontend.
     *
     * @return string
     */
    abstract public static function getName(): string;

    /**
     * Handle the bot actions intent.
     */
    abstract public function handle(): void;

    /**
     * Return the validation rules used when adding the action to a bot.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * If storing payload data, return the json encoded string.
     *
     * @return string|null
     */
    public function serializePayload(): ?string
    {
        return is_null($this->payload)
            ? null
            : json_encode($this->payload);
    }

    /**
     * @param BotAction $action
     * @return $this
     */
    public function setAction(BotAction $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @param Message $message
     * @param string $matchingTrigger
     * @return $this
     */
    public function setMessage(Message $message, string $matchingTrigger): self
    {
        $this->message = $message;

        $this->matchingTrigger = $matchingTrigger;

        return $this;
    }
}
