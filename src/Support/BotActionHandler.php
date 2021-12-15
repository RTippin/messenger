<?php

namespace RTippin\Messenger\Support;

use Illuminate\Support\Str;
use RTippin\Messenger\Exceptions\MessengerComposerException;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;

/**
 * To authorize the end user add the action handler to a bot, you must define the
 * 'authorize()' method and return true|false. If unauthorized, it will also hide
 * the handler from appearing in the available handlers list when adding actions
 * to a bot, as well as listed under a packaged bots install list. This does NOT
 * authorize being triggered once added to a bot action. This method will trigger
 * during a normal http request cycle, giving you access to auth/sessions/etc.
 *
 * @method bool authorize()
 */
abstract class BotActionHandler
{
    /**
     * @var bool
     */
    private static bool $isTesting = false;

    /**
     * @var Bot|null
     */
    protected ?Bot $bot = null;

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
    protected bool $isGroupAdmin = false;

    /**
     * @var string|null
     */
    protected ?string $senderIp = null;

    /**
     * @var bool
     */
    protected bool $shouldReleaseCooldown = false;

    /**
     * Helper method to globally set testing for action handlers. Allows
     * extended handlers to configure different paths when testing.
     *
     * @param  bool|null  $testing
     * @return bool
     */
    public static function isTesting(?bool $testing = null): bool
    {
        if (! is_null($testing)) {
            self::$isTesting = $testing;
        }

        return self::$isTesting;
    }

    /**
     * Return an array containing the handlers settings and overrides to use.
     * REQUIRED
     * - 'alias' will be used to locate and attach your handler to a bot.
     * - 'description' displayed to the frontend.
     * - 'name' displayed to the frontend.
     * OVERRIDES
     * 'unique' When set and true, the handler may only be used once across all bots in a thread.
     * 'triggers' overrides allowing end user to set the triggers. Only the given
     * trigger(s) will be used. Separate multiple via the pipe (|) or use an array.
     * 'match' overrides allowing end user to select matching method.
     * Available match methods:
     * ( any | contains | contains:caseless | contains:any | contains:any:caseless ).
     * ( exact | exact:caseless | starts:with | starts:with:caseless ).
     *
     * @return array{alias: string, description: string, name: string, unique: bool|null, triggers: array|string|null, match: string|null}
     */
    abstract public static function getSettings(): array;

    /**
     * Handle the bot actions intent. This is the last
     * method called when executing the handler.
     */
    abstract public function handle(): void;

    /**
     * Return the validation rules used when adding the action to a bot. Any rules
     * you define will have their keys/values stored in the action's payload. Return
     * an empty array if you have no extra data to validate or store.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * If you define extra validation rules, you may also define the validator
     * error messages here.
     *
     * @return array
     */
    public function errorMessages(): array
    {
        return [];
    }

    /**
     * If storing payload data, return the json encoded string.
     *
     * @param  array|null  $payload
     * @return string|null
     */
    public function serializePayload(?array $payload): ?string
    {
        return is_null($payload)
            ? null
            : json_encode($payload);
    }

    /**
     * Decode the action's payload.
     *
     * @param  string|null  $key
     * @return mixed|null
     */
    public function getPayload(?string $key = null)
    {
        return $this->action->getPayload($key);
    }

    /**
     * Returns the message with the trigger removed.
     *
     * @param  bool  $toLower
     * @return string|null
     */
    public function getParsedMessage(bool $toLower = false): ?string
    {
        $parsed = trim(
            Str::remove($this->matchingTrigger, $this->message->body, false)
        );

        if ($toLower) {
            $parsed = Str::lower($parsed);
        }

        return ! empty($parsed)
            ? $parsed
            : null;
    }

    /**
     * Returns an array of all words in the message with the trigger removed.
     *
     * @param  bool  $toLower
     * @return array|null
     */
    public function getParsedWords(bool $toLower = false): ?array
    {
        $parsed = $this->getParsedMessage($toLower);

        return ! empty($parsed)
            ? explode(' ', $parsed)
            : null;
    }

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
                                      ?string $senderIp = null): self
    {
        $this->thread = $thread;
        $this->action = $action;
        $this->bot = $action->bot;
        $this->message = $message;
        $this->matchingTrigger = $matchingTrigger;
        $this->isGroupAdmin = $isGroupAdmin;
        $this->senderIp = $senderIp;

        return $this;
    }

    /**
     * Set the thread we are composing to, and the bot as the sender,
     * and return the composer ready for an action!
     *
     * @return MessengerComposer
     *
     * @throws MessengerComposerException
     */
    public function composer(): MessengerComposer
    {
        return app(MessengerComposer::class)
            ->to($this->thread)
            ->from($this->bot);
    }

    /**
     * Should the handler not perform an action, you may call this to instruct
     * the handler to remove any cooldowns set after handle() completes.
     */
    public function releaseCooldown(): void
    {
        $this->shouldReleaseCooldown = true;
    }

    /**
     * If releaseCooldown() was called, this should return true.
     * When true, the action and bot cooldowns will be removed.
     *
     * @return bool
     */
    public function shouldReleaseCooldown(): bool
    {
        return $this->shouldReleaseCooldown;
    }
}
