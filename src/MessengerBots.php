<?php

namespace RTippin\Messenger;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Traits\ChecksReflection;

final class MessengerBots
{
    use ChecksReflection;

    /**
     * Methods we may use to match a trigger from within a message.
     */
    const BotActionMatchMethods = [
        'contains' => 'The trigger can be anywhere within a message. Cannot be part of or inside another word.',
        'contains:caseless' => 'Same as "contains", but is case insensitive.',
        'contains:any' => 'The trigger can be anywhere within a message, including inside another word.',
        'contains:any:caseless' => 'Same as "contains any", but is case insensitive.',
        'exact' => 'The trigger must match the message exactly.',
        'exact:caseless' => 'Same as "exact", but is case insensitive.',
        'starts:with' => 'The trigger must be the lead phrase within the message. Cannot be part of or inside another word.',
        'starts:with:caseless' => 'Same as "starts with", but is case insensitive.',
    ];

    /**
     * @var Collection
     */
    private Collection $handlers;

    /**
     * @var BotActionHandler|null
     */
    private ?BotActionHandler $activeHandler;

    /**
     * @var array
     */
    private array $handlerOverrides;

    /**
     * @var array
     */
    private array $resolvedHandlerData;

    /**
     * MessengerBots constructor.
     */
    public function __construct()
    {
        $this->handlers = new Collection([]);
        $this->activeHandler = null;
        $this->handlerOverrides = [];
        $this->resolvedHandlerData = [];
    }

    /**
     * Get all bot handler classes.
     *
     * @return array
     */
    public function getHandlerClasses(): array
    {
        return $this->handlers->keys()->toArray();
    }

    /**
     * Get all or an individual bot handlers settings.
     *
     * @param string|null $handlerOrAlias
     * @return array|null
     */
    public function getHandlerSettings(?string $handlerOrAlias = null): ?array
    {
        if (is_null($handlerOrAlias)) {
            return $this->handlers->values()->toArray();
        }

        $handler = $this->findHandler($handlerOrAlias);

        return $handler ? $this->handlers->get($handler) : null;
    }

    /**
     * Get all bot handler aliases.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return $this->handlers
            ->map(fn ($settings) => $settings['alias'])
            ->flatten()
            ->toArray();
    }

    /**
     * Get all available match methods.
     *
     * @return array
     */
    public function getMatchMethods(): array
    {
        return (new Collection(self::BotActionMatchMethods))
            ->keys()
            ->toArray();
    }

    /**
     * Locate a valid handler class using the class itself, or an alias.
     *
     * @param string $handlerOrAlias
     * @return string|null
     */
    public function findHandler(string $handlerOrAlias): ?string
    {
        if ($this->handlers->has($handlerOrAlias)) {
            return $handlerOrAlias;
        }

        return $this->handlers->search(function ($settings) use ($handlerOrAlias) {
            return $settings['alias'] === $handlerOrAlias;
        }) ?: null;
    }

    /**
     * Check if the given handler or alias is valid.
     *
     * @param string|null $handlerOrAlias
     * @return bool
     */
    public function isValidHandler(?string $handlerOrAlias): bool
    {
        return (bool) $this->findHandler($handlerOrAlias ?? '');
    }

    /**
     * Set the handlers we want to register. You may add more dynamically,
     * or choose to overwrite existing.
     *
     * @param array $actions
     * @param bool $overwrite
     * @return $this
     */
    public function setHandlers(array $actions, bool $overwrite = false): self
    {
        if ($overwrite) {
            $this->handlers = new Collection([]);
        }

        foreach ($actions as $action) {
            if ($this->checkIsSubclassOf($action, BotActionHandler::class)) {
                /** @var BotActionHandler $action */
                $this->handlers[$action] = $action::getSettings();
            }
        }

        return $this;
    }

    /**
     * Instantiate the concrete handler class using the class or alias provided.
     *
     * @param string $handlerOrAlias
     * @return BotActionHandler
     * @throws BotException
     */
    public function initializeHandler(string $handlerOrAlias): BotActionHandler
    {
        $handler = $this->findHandler($handlerOrAlias);

        if (is_null($handler)) {
            throw new BotException('Invalid bot handler.');
        }

        $this->activeHandler = app($handler);

        return $this->activeHandler;
    }

    /**
     * @return bool
     */
    public function isActiveHandlerSet(): bool
    {
        return ! is_null($this->activeHandler);
    }

    /**
     * Return the current active handler.
     *
     * @return BotActionHandler|null
     */
    public function getActiveHandler(): ?BotActionHandler
    {
        return $this->activeHandler;
    }

    /**
     * @return array
     */
    public function getResolvedHandlerData(): array
    {
        return $this->resolvedHandlerData;
    }

    /**
     * @return $this
     */
    public function getInstance(): self
    {
        return $this;
    }

    /**
     * @param array $request
     * @return $this
     * @throws BotException|ValidationException
     */
    public function resolveHandlerFromRequest(array $request): self
    {
        $this->handlerOverrides = [];
        $this->resolvedHandlerData = [];

        $handler = Validator::make($request, [
            'handler' => ['required', Rule::in($this->getAliases())],
        ])->validate()['handler'];

        $this->handlerOverrides['handler'] = $this->findHandler($handler);

        $this->initializeHandler($handler);

        $this->resolvedHandlerData = $this->mergeFinalRules(
            $this->validateHandlerSettings($request)
        );

        return $this;
    }

    /**
     * @param array $request
     * @return array
     * @throws ValidationException
     */
    private function validateHandlerSettings(array $request): array
    {
        return Validator::make($request, $this->generateRules())->validate();
    }

    /**
     * @return array
     */
    private function generateRules(): array
    {
        $mergedRuleset = array_merge($this->baseRuleset(), $this->getActiveHandler()->rules());

        $overrides = $this->getActiveHandler()::getSettings();

        if (array_key_exists('match', $overrides)) {
            Arr::forget($mergedRuleset, 'match');
            $this->handlerOverrides['match'] = $overrides['match'];
        }

        if (array_key_exists('triggers', $overrides)) {
            Arr::forget($mergedRuleset, 'triggers');
            $this->handlerOverrides['triggers'] = $this->formatTriggers($overrides['triggers']);
        }

        return $mergedRuleset;
    }

    /**
     * @param array $data
     * @return array
     */
    private function mergeFinalRules(array $data): array
    {
        return [
            'handler' => $this->handlerOverrides['handler'],
            'match' => $this->handlerOverrides['match'] ?? $data['match'],
            'triggers' => $this->handlerOverrides['triggers'] ?? $this->formatTriggers($data['triggers']),
            'admin_only' => $data['admin_only'],
            'cooldown' => $data['cooldown'],
            'enabled' => $data['enabled'],
            'payload' => $this->generatePayload($data),
        ];
    }

    /**
     * @param array $data
     * @return string|null
     */
    private function generatePayload(array $data): ?string
    {
        $payload = (new Collection($data))
            ->reject(fn ($value, $key) => in_array($key, array_keys($this->baseRuleset())))
            ->toArray();

        if (count($payload)) {
            return $this->getActiveHandler()->serializePayload($payload);
        }

        return null;
    }

    /**
     * @param string $triggers
     * @return string
     */
    private function formatTriggers(string $triggers): string
    {
        return (new Collection(preg_split('/[|,]/', $triggers)))
            ->transform(fn ($item) => trim($item))
            ->implode('|');
    }

    /**
     * The default ruleset we will validate against.
     *
     * @return array
     */
    private function baseRuleset(): array
    {
        return [
            'match' => ['required', 'string', Rule::in($this->getMatchMethods())],
            'cooldown' => ['required', 'integer', 'between:0,900'],
            'admin_only' => ['required', 'boolean'],
            'enabled' => ['required', 'boolean'],
            'triggers' => ['required', 'string'],
        ];
    }
}
