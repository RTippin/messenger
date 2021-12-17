<?php

namespace RTippin\Messenger\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use RTippin\Messenger\Facades\MessengerBots;

class PackagedBotInstallDTO implements Arrayable
{
    /**
     * @var BotActionHandlerDTO
     */
    public BotActionHandlerDTO $handler;

    /**
     * @var Collection
     */
    public Collection $data;

    /**
     * @param  string  $handler
     * @param  array  $data
     */
    public function __construct(string $handler, array $data)
    {
        $this->handler = MessengerBots::getHandler($handler);
        $this->data = $this->formatData($data);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->handler->toArray();
    }

    /**
     * The data parameters we allow per handler defined in a packaged bot can be empty,
     * a single associative array, or an array or associative arrays. A collection of
     * associative arrays will always be returned. The default parameters will be merged
     * with the given data supplied. To ensure bot handlers flagged as unique are not
     * defined to install more than once, we loop through the handlers and only take
     * the first set of data parameters for unique flagged handlers.
     *
     * @param  array|null  $data
     * @return Collection
     */
    private function formatData(array $data): Collection
    {
        $defaults = [
            'enabled' => true,
            'cooldown' => 30,
            'admin_only' => false,
        ];

        if (! count($data)) {
            return Collection::make([$defaults]);
        }

        $data = Arr::isAssoc($data) ? [$data] : $data;

        return Collection::make($data)->mapWithKeys(
            fn (array $value, int $key) => [$key => array_merge($defaults, $value)]
        )->reject(
            fn (array $value, int $key) => $this->handler->unique && $key > 0
        );
    }
}
