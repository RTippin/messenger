<?php

namespace RTippin\Messenger;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use RTippin\Messenger\Contracts\MessageTypeProvider;

class MessengerTypes
{

    /**
     * @var Collection|MessageTypeProvider[]
     */
    private Collection $messageTypes;

    /**
     * @var Collection|int[]
     */
    private Collection $verboseIndex;


    /**
     * Messenger constructor.
     */
    public function __construct()
    {
        $this->messageTypes = Collection::make();
        $this->verboseIndex = Collection::make();
    }

    /**
     * Set all providers we want to use in this messenger system.
     *
     * @param array $messageTypes
     * @param bool $overwrite
     * @return void
     */
    public function registerProviders(array $messageTypes, bool $overwrite = false): void
    {
        if ($overwrite) {
            $this->messageTypes = Collection::make();
            $this->verboseIndex = Collection::make();
        }

        foreach ($messageTypes as $type) {
            if (!is_subclass_of($type, MessageTypeProvider::class)) {
                throw new InvalidArgumentException("The given provider { $type } must implement the interface " . MessageTypeProvider::class);
            }

            if (is_string($type)) {
                $instance = new $type;
            } else {
                $instance = $type;
            }

            $this->messageTypes[$instance->getCode()] = $instance;
            $this->verboseIndex[$instance->getVerbose()] = $instance->getCode();
        }

//        dump($this->verboseIndex->toArray());
    }


    public function getMessageTypes()
    {
        return $this->messageTypes;
    }

    public function code(string $type)
    {
        return $this->verboseIndex[$type]
            ?? throw new InvalidArgumentException('Invalid type specified: ' . $type);
    }

    public function getMessageType(string $type): ?MessageTypeProvider
    {
        return $this->messageTypes->get($type);
    }

    public function getSystemTypes(): array
    {
        return $this->getSystemTypesProviders()
            ->map(fn(MessageTypeProvider $t) => $t->getCode())
            ->toArray();
    }

    public function getSystemTypesProviders()
    {
        return $this->messageTypes->filter(fn(MessageTypeProvider $mtp) => $mtp->isSystemType());
    }

    public function getNonSystemTypes(): array
    {
        return $this->getNonSystemTypesProviders()
            ->map(fn(MessageTypeProvider $t) => $t->getCode())
            ->toArray();
    }

    public function getNonSystemTypesProviders(): Collection
    {
        return $this->messageTypes
            ->filter(fn(MessageTypeProvider $mtp) => !$mtp->isSystemType());
    }
}
