<?php

namespace RTippin\Messenger;

use Illuminate\Support\Collection;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Traits\ChecksReflection;

final class MessengerBots
{
    use ChecksReflection;

    /**
     * @var Collection
     */
    private Collection $handlers;

    /**
     * @var BotActionHandler|null
     */
    private ?BotActionHandler $activeHandler;

    /**
     * MessengerBots constructor.
     */
    public function __construct()
    {
        $this->handlers = new Collection([]);
        $this->activeHandler = null;
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
     * @param string $handlerOrAlias
     * @return bool
     */
    public function isValidHandler(string $handlerOrAlias): bool
    {
        return (bool) $this->findHandler($handlerOrAlias);
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
}
