<?php

namespace RTippin\Messenger\Actions\Bots;

use RTippin\Messenger\Contracts\ActionHandler;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;

abstract class BotActionHandler implements ActionHandler
{
    /**
     * @var string
     */
    public static string $description = 'Bot action description.';

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
