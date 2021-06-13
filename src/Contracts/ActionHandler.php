<?php

namespace RTippin\Messenger\Contracts;

use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;

interface ActionHandler
{
    /**
     * Return an array containing the handlers settings and overrides we will use.
     * REQUIRED
     * - 'alias' will be used to locate and attach your handler to a bot.
     * - 'description' displayed to the frontend.
     * - 'name' displayed to the frontend.
     * OVERRIDES
     * 'unique' When set and true, the handler may only be used once on any bots within a thread.
     * 'triggers' overrides allowing end user to set the triggers. Only the given
     * trigger(s) will be used. Separate multiple via the pipe (|) or use an array.
     * 'match' overrides allowing end user to select matching method.
     * Available match methods:
     * ( contains | contains:caseless | contains:any | contains:any:caseless ).
     * ( exact | exact:caseless | starts:with | starts:with:caseless ).
     *
     * <code>
     * return [
     *     'alias' => 'bot_alias',
     *     'description' => 'Bot description.',
     *     'name' => 'Bot Name',
     *     'unique' => true, //optional
     *     'triggers' => '!h|!help', //optional
     *     'match' => 'exact' //optional
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
     * @param array|null $payload
     * @return string|null
     */
    public function serializePayload(?array $payload): ?string;
}
