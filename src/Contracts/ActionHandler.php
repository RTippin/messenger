<?php

namespace RTippin\Messenger\Contracts;

use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;

interface ActionHandler
{
    /**
     * Set the alias we will use when attaching the handler to
     * a bot model via a form post.
     *
     * @return string
     */
    public static function getAlias(): string;

    /**
     * Set the description of the handler.
     *
     * @return string
     */
    public static function getDescription(): string;

    /**
     * Set the name of the handler we will display to the frontend.
     *
     * @return string
     */
    public static function getName(): string;

    /**
     * Handle the bot actions intent. This is the last
     * method called when executing the handler.
     */
    public function handle(): void;

    /**
     * Set the message and trigger used for a message handler.
     *
     * @param Message $message
     * @param string $matchingTrigger
     * @return $this
     */
    public function setMessage(Message $message, string $matchingTrigger);

    /**
     * Set the BotAction.
     *
     * @param BotAction $action
     * @return $this
     */
    public function setAction(BotAction $action);

    /**
     * Return the validation rules used when adding the action to a bot.
     *
     * @return array
     */
    public function rules(): array;

    /**
     * If storing payload data, return the json encoded string.
     *
     * @return string|null
     */
    public function serializePayload(): ?string;
}
