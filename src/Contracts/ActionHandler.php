<?php

namespace RTippin\Messenger\Contracts;

use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;

interface ActionHandler
{
    /**
     * Set the given settings for the handler. Return an array containing the
     * handlers settings. The alias we will use when attaching the handler
     * to a bot model via a form post. The name and description will be
     * displayed to the frontend. Unique will only allow the handler to
     * be used once in a given thread.
     *
     * <code>
     * return [
     *     'alias' => 'bot_alias',
     *     'description' => 'Bot description.',
     *     'name' => 'Bot Name',
     *     'unique' => false,
     * ];
     * </code>
     *
     * @return array
     */
    public static function getSettings(): array;

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
     * Set the BotAction. We also set the messenger provider to the
     * bot the action belongs to.
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
