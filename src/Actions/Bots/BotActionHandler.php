<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Support\Str;
use RTippin\Messenger\Contracts\ActionHandler;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\MessengerComposer;

/**
 * To authorize the end user add the action handler to a bot, you must define the
 * 'authorize()' method and return true|false. If unauthorized, it will also hide
 * the handler from appearing in the available handlers list when adding actions
 * to a bot. This does NOT authorize being triggered once added to a bot action.
 * This method will be called during a normal http request cycle, giving you
 * access to auth/sessions/etc.
 *
 * @method bool authorize()
 */
abstract class BotActionHandler implements ActionHandler
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
     * @inheritDoc
     */
    public static function isTesting(?bool $testing = null): bool
    {
        if (! is_null($testing)) {
            self::$isTesting = $testing;
        }

        return self::$isTesting;
    }

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
     * @inheritDoc
     */
    public function getParsedWords(bool $toLower = false): ?array
    {
        $parsed = $this->getParsedMessage($toLower);

        return ! empty($parsed)
            ? explode(' ', $parsed)
            : null;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function composer(): MessengerComposer
    {
        return app(MessengerComposer::class)
            ->to($this->thread)
            ->from($this->bot);
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
