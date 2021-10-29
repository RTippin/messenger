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
     * Return an array containing the handlers settings and overrides to use.
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
     * @return array{alias: string, description: string, name: string, unique: bool|null, triggers: array|string|null, match: string|null}
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
     *
     * @throws InvalidProviderException|MessengerComposerException
     */
    public function composer(): MessengerComposer;

    /**
     * Sets the relevant data used when processing a handler from a message trigger.
     *
     * @param  Thread  $thread
     * @param  BotAction  $action
     * @param  Message  $message
     * @param  string|null  $matchingTrigger
     * @param  bool  $isGroupAdmin
     * @param  string|null  $senderIp
     * @return $this
     */
    public function setDataForHandler(Thread $thread,
                                      BotAction $action,
                                      Message $message,
                                      ?string $matchingTrigger = null,
                                      bool $isGroupAdmin = false,
                                      ?string $senderIp = null);

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
     * you define will have their keys/values stored in the action's payload. Return
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
     * @param  array|null  $payload
     * @return string|null
     */
    public function serializePayload(?array $payload): ?string;

    /**
     * Decode the action's payload.
     *
     * @param  string|null  $key
     * @return array|string|null
     */
    public function getPayload(?string $key = null);

    /**
     * Returns the message with the trigger removed.
     *
     * @param  bool  $toLower
     * @return string|null
     */
    public function getParsedMessage(bool $toLower = false): ?string;

    /**
     * Returns an array of all words in the message with the trigger removed.
     *
     * @param  bool  $toLower
     * @return array|null
     */
    public function getParsedWords(bool $toLower = false): ?array;

    /**
     * Helper method to globally set testing for action handlers. Allows
     * extended handlers to configure different paths when testing.
     *
     * @param  bool|null  $testing
     * @return bool
     */
    public static function isTesting(?bool $testing = null): bool;
}
