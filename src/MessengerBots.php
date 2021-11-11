<?php

namespace RTippin\Messenger;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO;
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
     * @var Collection|BotActionHandlerDTO[]
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

            $this->handlers[$handler] = new BotActionHandlerDTO($handler);
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
     * If authorize is set and true, initialize the handler to
     * pass its authorize method, otherwise returning true.
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
}
