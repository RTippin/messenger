<?php

namespace RTippin\Messenger;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Support\BotActionHandler;
use RTippin\Messenger\Support\PackagedBot;

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
     * @var Collection|BotActionHandlerDTO[]
     */
    private Collection $handlers;

    /**
     * @var Collection
     */
    private Collection $packagedBots;

    /**
     * @var BotActionHandler|null
     */
    private ?BotActionHandler $activeHandler = null;

    /**
     * @var string|null
     */
    private ?string $activeHandlerClass = null;

    /**
     * MessengerBots constructor.
     */
    public function __construct()
    {
        $this->handlers = new Collection;
        $this->packagedBots = new Collection;
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
                throw new InvalidArgumentException("The given handler { $handler } must extend ".BotActionHandler::class);
            }

            $this->handlers[$handler] = new BotActionHandlerDTO($handler);
        }
    }

    /**
     * @param  array  $packagedBots
     * @param  bool  $overwrite
     */
    public function registerPackagedBots(array $packagedBots, bool $overwrite = false): void
    {
        if ($overwrite) {
            $this->packagedBots = new Collection;
        }

        foreach ($packagedBots as $package) {
            if (! is_subclass_of($package, PackagedBot::class)) {
                throw new InvalidArgumentException("The given package { $package } must extend ".PackagedBot::class);
            }

            $this->packagedBots[$package] = new PackagedBotDTO($package);
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
            ->filter(fn (BotActionHandlerDTO $handler) => $handler->unique)
            ->keys()
            ->toArray();
    }

    /**
     * Get all or an individual bot handlers DTO.
     *
     * @param  string|null  $handlerOrAlias
     * @return BotActionHandlerDTO|Collection|null
     */
    public function getHandlersDTO(?string $handlerOrAlias = null)
    {
        if (is_null($handlerOrAlias)) {
            return $this->handlers
                ->sortBy('name')
                ->values();
        }

        return $this->handlers->get(
            $this->findHandler($handlerOrAlias)
        );
    }

    /**
     * @param  string|null  $handlerOrAlias
     * @return BotActionHandlerDTO|Collection|null
     *
     * @deprecated Use getHandlersDTO.
     */
    public function getHandlerSettings(?string $handlerOrAlias = null)
    {
        return $this->getHandlersDTO($handlerOrAlias);
    }

    /**
     * Returns the handlers the end user is authorized to view/add.
     *
     * @return Collection
     */
    public function getAuthorizedHandlers(): Collection
    {
        return $this->handlers
            ->sortBy('name')
            ->filter(fn (BotActionHandlerDTO $handler) => $this->authorizesHandler($handler))
            ->values();
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
            ->map(fn (BotActionHandlerDTO $handler) => $handler->alias)
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
            fn (BotActionHandlerDTO $handler) =>  $handler->alias === $handlerOrAlias
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
     * Flush any active handler and overrides set.
     */
    public function flush(): void
    {
        $this->activeHandler = null;
        $this->activeHandlerClass = null;
    }

    /**
     * Get all packaged bot classes.
     *
     * @return array
     */
    public function getPackagedBotClasses(): array
    {
        return $this->packagedBots->keys()->toArray();
    }

    /**
     * Get all packaged bot aliases.
     *
     * @return array
     */
    public function getPackagedBotAliases(): array
    {
        return $this->packagedBots
            ->sortBy('alias')
            ->map(fn (PackagedBotDTO $package) => $package->alias)
            ->flatten()
            ->toArray();
    }

    /**
     * Returns the packaged bots the end user is authorized to view/add.
     *
     * @return Collection|PackagedBotDTO[]
     */
    public function getAuthorizedPackagedBots(): Collection
    {
        return $this->packagedBots
            ->sortBy('name')
            ->filter(fn (PackagedBotDTO $package) => $this->authorizesPackagedBot($package))
            ->values();
    }

    /**
     * @return Collection|PackagedBotDTO[]
     */
    public function getPackagedBotsDTO(): Collection
    {
        return $this->packagedBots->values();
    }

    /**
     * Locate a valid packaged bot class using the class itself, or an alias.
     *
     * @param  string|null  $packageOrAlias
     * @return string|null
     */
    public function findPackagedBot(?string $packageOrAlias = null): ?string
    {
        if ($this->packagedBots->has($packageOrAlias)) {
            return $packageOrAlias;
        }

        return $this->packagedBots->search(
            fn (PackagedBotDTO $package) =>  $package->alias === $packageOrAlias
        ) ?: null;
    }

    /**
     * Check if the given packaged bot or alias is valid.
     *
     * @param  string|null  $packageOrAlias
     * @return bool
     */
    public function isValidPackagedBot(?string $packageOrAlias = null): bool
    {
        return (bool) $this->findPackagedBot($packageOrAlias);
    }

    /**
     * Instantiate the concrete packaged bot class using the class or alias provided.
     *
     * @param  string|null  $packageOrAlias
     * @return PackagedBot
     *
     * @throws BotException
     */
    public function initializePackagedBot(?string $packageOrAlias = null): PackagedBot
    {
        $package = $this->findPackagedBot($packageOrAlias);

        if (is_null($package)) {
            throw new BotException('Invalid bot package.');
        }

        return app($package);
    }

    /**
     * If the handler requires authorization, initialize the handler
     * and execute its authorize method, otherwise return true.
     *
     * @param  BotActionHandlerDTO  $handler
     * @return bool
     *
     * @throws BotException
     */
    private function authorizesHandler(BotActionHandlerDTO $handler): bool
    {
        if ($handler->shouldAuthorize) {
            return $this->initializeHandler($handler->class)->authorize();
        }

        return true;
    }

    /**
     * If the package requires authorization, initialize the package
     * and execute its authorize method, otherwise return true.
     *
     * @param  PackagedBotDTO  $package
     * @return bool
     *
     * @throws BotException
     */
    private function authorizesPackagedBot(PackagedBotDTO $package): bool
    {
        if ($package->shouldAuthorize) {
            return $this->initializePackagedBot($package->class)->authorize();
        }

        return true;
    }
}
