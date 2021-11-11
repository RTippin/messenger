<?php

namespace RTippin\Messenger\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;

class ResolvedBotHandlerDTO implements Arrayable
{
    /**
     * @var BotActionHandlerDTO
     */
    public BotActionHandlerDTO $handlerDTO;

    /**
     * @var string
     */
    public string $matchMethod;

    /**
     * @var bool
     */
    public bool $enabled;

    /**
     * @var bool
     */
    public bool $adminOnly;

    /**
     * @var int
     */
    public int $cooldown;

    /**
     * @var string|null
     */
    public ?string $triggers;

    /**
     * @var string|null
     */
    public ?string $payload;

    /**
     * @param  BotActionHandlerDTO  $handlerDTO
     * @param  string  $matchMethod
     * @param  bool  $enabled
     * @param  bool  $adminOnly
     * @param  int  $cooldown
     * @param  string|null  $triggers
     * @param  string|null  $payload
     */
    public function __construct(BotActionHandlerDTO $handlerDTO,
                                string $matchMethod,
                                bool $enabled,
                                bool $adminOnly,
                                int $cooldown,
                                ?string $triggers,
                                ?string $payload)
    {
        $this->handlerDTO = $handlerDTO;
        $this->matchMethod = $matchMethod;
        $this->enabled = $enabled;
        $this->adminOnly = $adminOnly;
        $this->cooldown = $cooldown;
        $this->triggers = $triggers;
        $this->payload = $payload;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'handler' => $this->handlerDTO->toArray(),
            'match' => $this->matchMethod,
            'triggers' => $this->triggers,
            'admin_only' => $this->adminOnly,
            'cooldown' => $this->cooldown,
            'enabled' => $this->enabled,
            'payload' => $this->payload,
        ];
    }
}
