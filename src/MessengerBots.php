<?php

namespace RTippin\Messenger;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Exceptions\BotException;

final class MessengerBots
{
    /**
     * Methods we may use to match a trigger from within a message.
     */
    const MATCH_ANY = 'any';
    const MATCH_CONTAINS = 'contains';
    const MATCH_CONTAINS_CASELESS = 'contains:caseless';
    const MATCH_CONTAINS_ANY = 'contains:any';
    const MATCH_CONTAINS_ANY_CASELESS = 'contains:any:caseless';
    const MATCH_EXACT = 'exact';
    const MATCH_EXACT_CASELESS = 'exact:caseless';
    const MATCH_STARTS_WITH = 'starts:with';
    const MATCH_STARTS_WITH_CASELESS = 'starts:with:caseless';
    const BotActionMatchMethods = [
        self::MATCH_ANY => 'The action will be triggered for any message sent.',
        self::MATCH_CONTAINS => 'The trigger can be anywhere within a message. Cannot be part of or inside another word.',
        self::MATCH_CONTAINS_CASELESS => 'Same as "contains", but is case insensitive.',
        self::MATCH_CONTAINS_ANY => 'The trigger can be anywhere within a message, including inside another word.',
        self::MATCH_CONTAINS_ANY_CASELESS => 'Same as "contains any", but is case insensitive.',
        self::MATCH_EXACT => 'The trigger must match the message exactly.',
        self::MATCH_EXACT_CASELESS => 'Same as "exact", but is case insensitive.',
        self::MATCH_STARTS_WITH => 'The trigger must be the lead phrase within the message. Cannot be part of or inside another word.',
        self::MATCH_STARTS_WITH_CASELESS => 'Same as "starts with", but is case insensitive.',
    ];

    /**
     * @var Collection
     */
    private Collection $handlers;

    /**
     * @var BotActionHandler|null
     */
    private ?BotActionHandler $activeHandler = null;

    /**
     * @var string|null
     */
    private ?string $activeHandlerClass = null;

    /**
     * @var array|null
     */
    private ?array $activeHandlerSettings = null;

    /**
     * MessengerBots constructor.
     */
    public function __construct()
    {
        $this->handlers = new Collection;
    }

    /**
     * Set the handlers we want to register. These can then be attached to
     * a bots action, and executed when a match is found.
     *
     * @param  array  $handlers
     * @param  bool  $overwrite
     */
    public function registerHandlers(array $handlers, bool $overwrite = false): void
    {
        if ($overwrite) {
            $this->handlers = new Collection;
        }

        foreach ($handlers as $handler) {
            if (! is_subclass_of($handler, BotActionHandler::class)) {
                throw new InvalidArgumentException("The given handler { $handler } must extend our base handler ".BotActionHandler::class);
            }

            $this->handlers[$handler] = $this->makeHandlerSettings($handler);
        }
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
     * Get all bot handler classes marked as unique.
     *
     * @return array
     */
    public function getUniqueHandlerClasses(): array
    {
        return $this->handlers
            ->filter(fn ($settings) => $settings['unique'] === true)
            ->keys()
            ->toArray();
    }

    /**
     * Get all or an individual bot handlers settings.
     *
     * @param  string|null  $handlerOrAlias
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

        return $this->handlers->get(
            $this->findHandler($handlerOrAlias)
        );
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
     * @param  string|null  $match
     * @return string|null
     */
    public function getMatchDescription(?string $match = null): ?string
    {
        return self::BotActionMatchMethods[$match] ?? null;
    }

    /**
     * Locate a valid handler class using the class itself, or an alias.
     *
     * @param  string|null  $handlerOrAlias
     * @return string|null
     */
    public function findHandler(?string $handlerOrAlias = null): ?string
    {
        if ($this->handlers->has($handlerOrAlias)) {
            return $handlerOrAlias;
        }

        return $this->handlers->search(
            fn ($settings) =>  $settings['alias'] === $handlerOrAlias
        ) ?: null;
    }

    /**
     * Check if the given handler or alias is valid.
     *
     * @param  string|null  $handlerOrAlias
     * @return bool
     */
    public function isValidHandler(?string $handlerOrAlias = null): bool
    {
        return (bool) $this->findHandler($handlerOrAlias);
    }

    /**
     * Instantiate the concrete handler class using the class or alias provided.
     * If the handler matches what we already have initialized,
     * return that instance instead.
     *
     * @param  string|null  $handlerOrAlias
     * @return BotActionHandler
     *
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
        $this->activeHandlerSettings = $this->getHandlerSettings($handler);

        return $this->activeHandler;
    }

    /**
     * Resolve a bot handler using the data parameters. Validate against our base
     * ruleset and any custom rules or overrides on the handler class itself.
     * Return the final data array we will use to store the BotAction model.
     * The handler validator can be overwritten if an action's handler class
     * or alias is supplied. We will then attempt to initialize it directly.
     *
     * @param  array  $data
     * @param  string|null  $handlerOrAlias
     * @return array
     *
     * @throws ValidationException|BotException
     */
    public function resolveHandlerData(array $data, ?string $handlerOrAlias = null): array
    {
        // Validate and initialize the handler / alias
        $this->initializeHandler(
            $handlerOrAlias ?: $this->validateHandlerAlias($data)
        );

        // Generate the overrides for the handler.
        $overrides = $this->getHandlerOverrides();

        // Validate against the handler settings, omitting overrides
        $validated = $this->validateHandlerSettings($data, $overrides);

        // Gather the generated data array from our validated and merged properties
        $generated = $this->generateHandlerDataForStoring($validated, $overrides);

        // Validate the final formatted triggers to ensure it is
        // not empty if our match method was not "MATCH_ANY"
        $this->validateFormattedTriggers($generated);

        return $generated;
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
     * Flush any active handler and overrides set.
     */
    public function flush(): void
    {
        $this->activeHandler = null;
        $this->activeHandlerClass = null;
        $this->activeHandlerSettings = null;
    }

    /**
     * If authorize is set and true, initialize the handler to
     * pass its authorize method, otherwise returning true.
     *
     * @param $settings
     * @return bool
     *
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
     * @param  string  $handler
     * @return array
     */
    private function makeHandlerSettings(string $handler): array
    {
        /** @var BotActionHandler $handler */
        $settings = $handler::getSettings();

        $match = $settings['match'] ?? null;

        if (array_key_exists('triggers', $settings)
            && ! is_null($settings['triggers'])
            && $match !== self::MATCH_ANY) {
            $settings['triggers'] = explode('|', $this->formatTriggers($settings['triggers']));
        }

        return [
            'alias' => $settings['alias'],
            'description' => $settings['description'],
            'name' => $settings['name'],
            'unique' => $settings['unique'] ?? false,
            'authorize' => method_exists($handler, 'authorize'),
            'triggers' => $match === self::MATCH_ANY ? null : ($settings['triggers'] ?? null),
            'match' => $match,
        ];
    }

    /**
     * @param  array  $data
     * @return string
     *
     * @throws ValidationException
     */
    private function validateHandlerAlias(array $data): string
    {
        return Validator::make($data, [
            'handler' => ['required', Rule::in($this->getAliases())],
        ])->validate()['handler'];
    }

    /**
     * @return array
     */
    private function getHandlerOverrides(): array
    {
        $overrides = [];

        if (! is_null($this->activeHandlerSettings['match'])) {
            $overrides['match'] = $this->activeHandlerSettings['match'];
        }

        if (! is_null($this->activeHandlerSettings['triggers'])
            && $this->activeHandlerSettings['match'] !== self::MATCH_ANY) {
            $overrides['triggers'] = $this->formatTriggers($this->activeHandlerSettings['triggers']);
        }

        return $overrides;
    }

    /**
     * @param  array  $data
     * @param  array  $overrides
     * @return array
     *
     * @throws ValidationException
     */
    private function validateHandlerSettings(array $data, array $overrides): array
    {
        $mergedRuleset = array_merge([
            'cooldown' => ['required', 'integer', 'between:0,900'],
            'admin_only' => ['required', 'boolean'],
            'enabled' => ['required', 'boolean'],
        ], $this->getActiveHandler()->rules());

        $validator = Validator::make(
            $data,
            $mergedRuleset,
            $this->generateErrorMessages()
        );

        $this->addConditionalHandlerValidations($validator, $overrides);

        return $validator->validate();
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     * @param  array  $overrides
     */
    private function addConditionalHandlerValidations(\Illuminate\Validation\Validator $validator, array $overrides): void
    {
        $validator->sometimes('match', [
            'required',
            'string',
            Rule::in($this->getMatchMethods()),
        ], fn () => ! array_key_exists('match', $overrides));

        $validator->sometimes('triggers', [
            'required',
            'array',
            'min:1',
        ], fn (Fluent $input) => $this->shouldValidateTriggers($input, $overrides));

        $validator->sometimes('triggers.*', [
            'required',
            'string',
        ], fn (Fluent $input) => $this->shouldValidateTriggers($input, $overrides));
    }

    /**
     * @param  Fluent  $input
     * @param  array  $overrides
     * @return bool
     */
    private function shouldValidateTriggers(Fluent $input, array $overrides): bool
    {
        return $this->triggersNotInOverrides($overrides)
            && $this->matchMethodIsNotMatchAny($input)
            && $this->matchMethodOverrideIsNotMatchAny($overrides);
    }

    /**
     * @param  array  $overrides
     * @return bool
     */
    private function triggersNotInOverrides(array $overrides): bool
    {
        return ! array_key_exists('triggers', $overrides);
    }

    /**
     * @param  Fluent  $input
     * @return bool
     */
    private function matchMethodIsNotMatchAny(Fluent $input): bool
    {
        return $input->get('match') !== self::MATCH_ANY;
    }

    /**
     * @param  array  $overrides
     * @return bool
     */
    private function matchMethodOverrideIsNotMatchAny(array $overrides): bool
    {
        return ! (array_key_exists('match', $overrides)
            && $overrides['match'] === self::MATCH_ANY);
    }

    /**
     * @param  array  $data
     * @return void
     *
     * @throws ValidationException
     */
    private function validateFormattedTriggers(array $data): void
    {
        if (! is_null($data['triggers'])) {
            Validator::make($data, [
                'triggers' => ['required', 'string'],
            ])->validate();
        }
    }

    /**
     * Merge our error messages with any custom messages defined on the handler.
     *
     * @return array
     */
    private function generateErrorMessages(): array
    {
        return array_merge([
            'triggers.*.required' => 'Trigger field is required.',
            'triggers.*.string' => 'A trigger must be a string.',
        ], $this->getActiveHandler()->errorMessages());
    }

    /**
     * Make the final data array we will pass to create a new BotAction.
     *
     * @param  array  $data
     * @param  array  $overrides
     * @return array
     */
    private function generateHandlerDataForStoring(array $data, array $overrides): array
    {
        return [
            'handler' => $this->activeHandlerClass,
            'unique' => $this->activeHandlerSettings['unique'],
            'authorize' => $this->activeHandlerSettings['authorize'],
            'name' => $this->activeHandlerSettings['name'],
            'match' => $overrides['match'] ?? $data['match'],
            'triggers' => $this->generateTriggers($data, $overrides),
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
     * @param  array  $data
     * @return string|null
     */
    private function generatePayload(array $data): ?string
    {
        $ruleKeys = [
            'match',
            'cooldown',
            'admin_only',
            'enabled',
            'triggers',
            'triggers.*',
        ];

        $payload = (new Collection($data))
            ->reject(fn ($value, $key) => in_array($key, $ruleKeys))
            ->toArray();

        if (count($payload)) {
            return $this->getActiveHandler()->serializePayload($payload);
        }

        return null;
    }

    /**
     * @param  array  $data
     * @param  array  $overrides
     * @return string|null
     */
    private function generateTriggers(array $data, array $overrides): ?string
    {
        $match = $overrides['match'] ?? $data['match'];

        if ($match === self::MATCH_ANY) {
            return null;
        }

        return $overrides['triggers'] ?? $this->formatTriggers($data['triggers']);
    }

    /**
     * Combine the final triggers to be a single string, separated by the
     * pipe (|), and removing duplicates.
     *
     * @param  null|string|array  $triggers
     * @return string|null
     */
    private function formatTriggers($triggers): ?string
    {
        if (is_null($triggers)) {
            return null;
        }

        $triggers = is_array($triggers)
            ? implode('|', $triggers)
            : $triggers;

        return (new Collection(preg_split('/[|,]/', $triggers)))
            ->transform(fn ($item) => trim($item))
            ->unique()
            ->filter()
            ->implode('|');
    }
}
