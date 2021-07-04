<?php

namespace RTippin\Messenger\Contracts;

use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Exceptions\MessengerComposerException;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\MessengerComposer;

interface ActionHandler
{
    /**
     * Return an array containing the handlers settings and overrides we will use.
     * REQUIRED
     * - 'alias' will be used to locate and attach your handler to a bot.
     * - 'description' displayed to the frontend.
     * - 'name' displayed to the frontend.
     * OVERRIDES
     * 'unique' When set and true, the handler may only be used once per bot.
     * in the list of available handlers, as well as to be added to a bot.
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
     * Set the thread we are composing to, and the bot as the sender,
     * and return the composer ready for an action!
     *
     * @return MessengerComposer
     * @throws InvalidProviderException|MessengerComposerException
     */
    public function composer(): MessengerComposer;

    /**
     * Set the BotAction.
     *
     * @param BotAction $action
     * @return $this
     */
    public function setAction(BotAction $action);

    /**
     * Set the thread we are working with.
     *
     * @param Thread $thread
     * @return $this
     */
    public function setThread(Thread $thread);

    /**
     * Set the message and trigger used for a message handler.
     *
     * @param Message $message
     * @param string|null $matchingTrigger
     * @param string|null $senderIp
     * @return $this
     */
    public function setMessage(Message $message, ?string $matchingTrigger, ?string $senderIp);

    /**
     * Should the handler not perform an action, you may call this to instruct
     * the handler to remove any cooldowns set after handle() completes.
     */
    public function releaseCooldown(): void;

    /**
     * If releaseCooldown() was called, this should return true.
     * When true, the action and bot cooldowns will be removed.
     *
     * @return bool
     */
    public function shouldReleaseCooldown(): bool;

    /**
     * Return the validation rules used when adding the action to a bot. Any rules
     * you define will have their keys/values stored in the actions payload. Return
     * an empty array if you have no extra data to validate or store.
     *
     * @return array
     */
    public function rules(): array;

    /**
     * If you define extra validation rules, you may also define the validator
     * error messages here.
     *
     * @return array
     */
    public function errorMessages(): array;

    /**
     * If storing payload data, return the json encoded string.
     *
     * @param array|null $payload
     * @return string|null
     */
    public function serializePayload(?array $payload): ?string;

    /**
     * Decode the actions payload.
     *
     * @param string|null $key
     * @return array|string|null
     */
    public function getPayload(?string $key = null);
}
