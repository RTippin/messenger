<?php

namespace RTippin\Messenger;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Support\Helpers;

final class MessengerBots
{
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
     * @var bool
     */
    private static bool $useUuid = false;

    /**
     * @var Collection
     */
    private Collection $handlers;

    /**
     * @var BotActionHandler|null
     */
    private ?BotActionHandler $activeHandler;

    /**
     * @var string|null
     */
    private ?string $activeHandlerClass;

    /**
     * @var array
     */
    private array $handlerOverrides;

    /**
     * MessengerBots constructor.
     */
    public function __construct()
    {
        $this->handlers = new Collection([]);
        $this->activeHandler = null;
        $this->activeHandlerClass = null;
        $this->handlerOverrides = [];
    }

    /**
     * This determines if we use UUID or BigInt on the bot model and migrations.
     *
     * @param bool|null $shouldUseUuids
     * @return bool
     */
    public static function shouldUseUuids(?bool $shouldUseUuids = null): bool
    {
        if (is_null($shouldUseUuids)) {
            return self::$useUuid;
        }

        self::$useUuid = $shouldUseUuids;

        return self::$useUuid;
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
            return $this->handlers
                ->sortBy('name')
                ->values()
                ->toArray();
        }

        $handler = $this->findHandler($handlerOrAlias);

        return $handler
            ? $this->handlers->get($handler)
            : null;
    }

    /**
     * Returns the handler settings the end user is authorized to view/add.
     *
     * @return array
     */
    public function getAuthorizedHandlers(): array
    {
        return $this->handlers
            ->sortBy('name')
            ->filter(fn ($settings) => $this->authorizesHandler($settings))
            ->values()
            ->toArray();
    }

    /**
     * Get all bot handler aliases.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return $this->handlers
            ->sortBy('alias')
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
        return array_keys(self::BotActionMatchMethods);
    }

    /**
     * Get the description for the match method.
     *
     * @param string|null $match
     * @return string|null
     */
    public function getMatchDescription(?string $match = null): ?string
    {
        return self::BotActionMatchMethods[$match] ?? null;
    }

    /**
     * Locate a valid handler class using the class itself, or an alias.
     *
     * @param string|null $handlerOrAlias
     * @return string|null
     */
    public function findHandler(?string $handlerOrAlias = null): ?string
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
    public function isValidHandler(?string $handlerOrAlias = null): bool
    {
        return (bool) $this->findHandler($handlerOrAlias ?? '');
    }

    /**
     * Set the handlers we want to register. These can then be attached to
     * a bots action, and executed when a match is found.
     *
     * @param array $handlers
     * @param bool $overwrite
     * @return $this
     */
    public function setHandlers(array $handlers, bool $overwrite = false): self
    {
        if ($overwrite) {
            $this->handlers = new Collection([]);
        }

        foreach ($handlers as $handler) {
            if (! Helpers::checkIsSubclassOf($handler, BotActionHandler::class)) {
                throw new InvalidArgumentException("The given handler { $handler } must extend our base handler ".BotActionHandler::class);
            }

            $this->handlers[$handler] = $this->makeHandlerSettings($handler);
        }

        return $this;
    }

    /**
     * Instantiate the concrete handler class using the class or alias provided.
     * If the handler matches what we already have initialized,
     * return that instance instead.
     *
     * @param string|null $handlerOrAlias
     * @return BotActionHandler
     * @throws BotException
     */
    public function initializeHandler(?string $handlerOrAlias = null): BotActionHandler
    {
        $handler = $this->findHandler($handlerOrAlias);

        if (is_null($handler)) {
            throw new BotException('Invalid bot handler.');
        }

        if ($this->isActiveHandlerSet()
            && $this->activeHandlerClass === $handler) {
            return $this->activeHandler;
        }

        $this->activeHandler = app($handler);
        $this->activeHandlerClass = $handler;

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
     * @return $this
     */
    public function getInstance(): self
    {
        return $this;
    }

    /**
     * Resolve a bot handler using the data parameters. Validate against our base
     * ruleset and any custom rules or overrides on the handler class itself.
     * Return the final data array we will use to store the BotAction model.
     * The handler validator can be overwritten if an actions handler class
     * or alias is supplied. We will then attempt to initialize it directly.
     *
     * @param array $data
     * @param string|null $handlerOrAlias
     * @return array
     * @throws ValidationException|BotException
     */
    public function resolveHandlerData(array $data, ?string $handlerOrAlias = null): array
    {
        // Reset the overrides array
        $this->handlerOverrides = [];

        // Validate and initialize the handler / alias
        $this->initializeHandler(
            $handlerOrAlias ?? $this->validateHandlerAlias($data)
        );

        // Gather the generated data array from our validated and merged properties
        $generated = $this->generateHandlerData(
            $this->validateHandlerSettings($data)
        );

        // Validate the final formatted triggers to ensure it is not empty
        $this->validateFormattedTriggers($generated);

        return $generated;
    }

    /**
     * @param array $data
     * @return string
     * @throws ValidationException
     */
    private function validateHandlerAlias(array $data): string
    {
        return Validator::make($data, [
            'handler' => ['required', Rule::in($this->getAliases())],
        ])->validate()['handler'];
    }

    /**
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    private function validateHandlerSettings(array $data): array
    {
        return Validator::make(
            $data,
            $this->generateRules(),
            $this->generateErrorMessages()
        )->validate();
    }

    /**
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    private function validateFormattedTriggers(array $data): void
    {
        Validator::make($data, [
            'triggers' => ['required', 'string'],
        ])->validate();
    }

    /**
     * Merge our base ruleset with the handlers defined rules. Remove and set
     * any overrides before validation.
     *
     * @return array
     */
    private function generateRules(): array
    {
        $mergedRuleset = array_merge($this->baseRuleset(), $this->getActiveHandler()->rules());

        $overrides = $this->getHandlerSettings($this->activeHandlerClass);

        if (! is_null($overrides['match'])) {
            Arr::forget($mergedRuleset, 'match');
            $this->handlerOverrides['match'] = $overrides['match'];
        }

        if (! is_null($overrides['triggers'])) {
            Arr::forget($mergedRuleset, 'triggers');
            Arr::forget($mergedRuleset, 'triggers.*');
            $this->handlerOverrides['triggers'] = $this->formatTriggers($overrides['triggers']);
        }

        return $mergedRuleset;
    }

    /**
     * Merge our error messages with any custom messages defined on the handler.
     *
     * @return array
     */
    private function generateErrorMessages(): array
    {
        return array_merge(
            $this->baseErrorMessages(),
            $this->getActiveHandler()->errorMessages()
        );
    }

    /**
     * Make the final data array we will pass to create a new BotAction.
     *
     * @param array $data
     * @return array
     */
    private function generateHandlerData(array $data): array
    {
        $settings = $this->getHandlerSettings($this->activeHandlerClass);

        return [
            'handler' => $this->activeHandlerClass,
            'unique' => $settings['unique'],
            'authorize' => $settings['authorize'],
            'name' => $settings['name'],
            'match' => $this->handlerOverrides['match'] ?? $data['match'],
            'triggers' => $this->handlerOverrides['triggers'] ?? $this->formatTriggers($data['triggers']),
            'admin_only' => $data['admin_only'],
            'cooldown' => $data['cooldown'],
            'enabled' => $data['enabled'],
            'payload' => $this->generatePayload($data),
        ];
    }

    /**
     * Strip any non-base rules from the array, then call to the handlers
     * serialize to json encode our payload.
     *
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
     * Combine the final triggers to be a single string, separated by the
     * pipe (|), and removing duplicates.
     *
     * @param string|array $triggers
     * @return string
     */
    private function formatTriggers($triggers): string
    {
        $triggers = is_array($triggers)
            ? implode('|', $triggers)
            : $triggers;

        return (new Collection(preg_split('/[|,]/', $triggers)))
            ->transform(fn ($item) => trim($item))
            ->unique()
            ->reject(fn ($value) => empty($value))
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
            'triggers' => ['required', 'array', 'min:1'],
            'triggers.*' => ['required', 'string'],
        ];
    }

    /**
     * The default error messages should validation fail.
     *
     * @return array
     */
    private function baseErrorMessages(): array
    {
        return [
            'triggers.*.required' => 'Trigger field is required.',
            'triggers.*.string' => 'A trigger must be a string.',
        ];
    }

    /**
     * If authorize is set and true, initialize the handler to
     * pass its authorize method, otherwise returning true.
     *
     * @param $settings
     * @return bool
     * @throws BotException
     */
    private function authorizesHandler($settings): bool
    {
        if ($settings['authorize'] ?? false) {
            return $this->initializeHandler($settings['alias'])->authorize();
        }

        return true;
    }

    /**
     * Generate the handler settings resource array.
     *
     * @param string $handler
     * @return array
     */
    private function makeHandlerSettings(string $handler): array
    {
        /** @var BotActionHandler $handler */
        $settings = $handler::getSettings();

        if (array_key_exists('triggers', $settings)) {
            $settings['triggers'] = explode('|', $this->formatTriggers($settings['triggers']));
        }

        return [
            'alias' => $settings['alias'],
            'description' => $settings['description'],
            'name' => $settings['name'],
            'unique' => $settings['unique'] ?? false,
            'authorize' => method_exists($handler, 'authorize'),
            'triggers' => $settings['triggers'] ?? null,
            'match' => $settings['match'] ?? null,
        ];
    }
}
