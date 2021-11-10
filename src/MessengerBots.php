<?php

namespace RTippin\Messenger;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Services\BotHandlerResolverService;

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
     * @param  array  $data
     * @param  string|null  $handlerOrAlias
     *
     * @deprecated Moved to BotHandlerResolverService.
     *
     * @return array
     *
     * @throws ValidationException|BotException
     */
    public function resolveHandlerData(array $data, ?string $handlerOrAlias = null): array
    {
        return app(BotHandlerResolverService::class)->resolve($data, $handlerOrAlias);
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
     * Return the current active handler's settings.
     *
     * @return array|null
     */
    public function getActiveHandlerSettings(): ?array
    {
        return $this->activeHandlerSettings;
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

        if ($this->shouldOverwriteTriggers($settings, $match)) {
            $settings['triggers'] = explode('|', BotAction::formatTriggers($settings['triggers']));
        }

        return [
            'alias' => $settings['alias'],
            'description' => $settings['description'],
            'name' => $settings['name'],
            'unique' => $settings['unique'] ?? false,
            'authorize' => method_exists($handler, 'authorize'),
            'triggers' => $this->getFinalizedTriggers($settings, $match),
            'match' => $match,
        ];
    }

    /**
     * @param  array  $settings
     * @param  string|null  $match
     * @return bool
     */
    private function shouldOverwriteTriggers(array $settings, ?string $match): bool
    {
        return array_key_exists('triggers', $settings)
            && ! is_null($settings['triggers'])
            && $match !== self::MATCH_ANY;
    }

    /**
     * @param  array  $settings
     * @param  string|null  $match
     * @return array|null
     */
    private function getFinalizedTriggers(array $settings, ?string $match): ?array
    {
        if ($match === self::MATCH_ANY) {
            return null;
        }

        return $settings['triggers'] ?? null;
    }
}
