<?php

namespace RTippin\Messenger\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;
use RTippin\Messenger\Support\PackagedBot;

class PackagedBotDTO implements Arrayable
{
    /**
     * @var string|PackagedBot
     */
    public string $class;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $description;

    /**
     * @var string|null
     */
    public ?string $avatar;

    /**
     * @var array
     */
    public array $installs;

    /**
     * @param  string  $packagedBot
     *
     * @see PackagedBot
     */
    public function __construct(string $packagedBot)
    {
        /** @var PackagedBot $package */
        $package = $packagedBot;

        $this->class = $packagedBot;
        $this->name = $package::name();
        $this->description = $package::description();
        $this->avatar = $package::avatar();
        $this->installs = $package::installs();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'avatar' => $this->avatar,
            'installs' => $this->installs,
        ];
    }
}
