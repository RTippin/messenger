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
     * @param  array|null  $data
     */
    public function __construct(string $handler, ?array $data)
    {
        $this->handler = MessengerBots::getHandlers($handler);
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
            fn ($value, $key) => [$key => array_merge($defaults, $value)]
        )->reject(
            fn ($value, $key) => $this->handler->unique && $key > 0
        );
    }
}
