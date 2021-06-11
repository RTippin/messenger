<?php

namespace RTippin\Messenger;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Traits\ChecksReflection;

final class MessengerBots
{
    use ChecksReflection;

    /**
     * @var array
     */
    private array $actions = [];

    /**
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @param array|null $actions
     * @return $this
     */
    public function setActions(?array $actions): self
    {
        $this->actions = [];

        if (is_array($actions) && count($actions)) {
            foreach ($actions as $action) {
                if ($this->checkIsSubclassOf($action, BotActionHandler::class)) {
                    array_push($this->actions, $action);
                }
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function getInstance(): self
    {
        return $this;
    }
}
