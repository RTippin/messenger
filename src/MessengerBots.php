<?php

namespace RTippin\Messenger;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Traits\ChecksReflection;

final class MessengerBots
{
    use ChecksReflection;

    /**
     * @var Application
     */
    private Application $app;

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
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->handlers = new Collection([]);
        $this->activeHandler = null;
    }

    /**
     * Get all handler classes.
     *
     * @return array
     */
    public function getHandlers(): array
    {
        return $this->handlers
            ->map(fn ($item, $key) => $key)
            ->flatten()
            ->toArray();
    }

    /**
     * Get all aliases.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return $this->handlers
            ->map(fn ($item) => $item['alias'])
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

        return $this->handlers->search(function ($values) use ($handlerOrAlias) {
            return $values['alias'] === $handlerOrAlias;
        }) ?: null;
    }

    /**
     * @param string $handlerOrAlias
     * @return bool
     */
    public function isValidHandler(string $handlerOrAlias): bool
    {
        return (bool) $this->findHandler($handlerOrAlias);
    }

    /**
     * @param string $handler
     * @return string|null
     */
    public function getHandlerDescription(string $handler): ?string
    {
        return $this->handlers->has($handler)
            ? $this->handlers->get($handler)['description']
            : null;
    }

    /**
     * @param array|null $actions
     * @return $this
     */
    public function setHandlers(?array $actions): self
    {
        if (is_array($actions) && count($actions)) {
            foreach ($actions as $action) {
                if ($this->checkIsSubclassOf($action, BotActionHandler::class)) {
                    /** @var BotActionHandler $action */
                    $this->handlers[$action] = $action::getSettings();
                }
            }
        }

        return $this;
    }

    /**
     * @param string $handlerOrAlias
     * @return BotActionHandler
     * @throws BindingResolutionException|BotException
     */
    public function initializeHandler(string $handlerOrAlias): BotActionHandler
    {
        $handler = $this->findHandler($handlerOrAlias);

        if (is_null($handler)) {
            throw new BotException('Invalid bot handler.');
        }

        $this->activeHandler = $this->app->make($handler);

        return $this->activeHandler;
    }

    /**
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
