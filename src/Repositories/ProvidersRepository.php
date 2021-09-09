<?php

namespace RTippin\Messenger\Repositories;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Messenger;

class ProvidersRepository
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * ProvidersRepository constructor.
     *
     * @param  Messenger  $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @param  string  $alias
     * @param  string  $id
     * @return MessengerProvider|null
     */
    public function getProviderUsingAliasAndId(string $alias, string $id): ?MessengerProvider
    {
        /** @var MessengerProvider|null $provider */
        $provider = $this->messenger->findAliasProvider($alias);

        return ! is_null($provider)
            ? $provider::find($id)
            : null;
    }
}
