<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use RTippin\Messenger\Http\Resources\RecipientThreadResource;
use RTippin\Messenger\RecipientThreadLocator;

class FindRecipientThread
{
    /**
     * Attempt to locate an existing private thread given
     * the alias and id given of another provider.
     *
     * @param RecipientThreadLocator $locator
     * @param string $alias
     * @param string $id
     * @return RecipientThreadResource
     */
    public function __invoke(RecipientThreadLocator $locator,
                             string $alias,
                             string $id): RecipientThreadResource
    {
        $locator->setAlias($alias)
            ->setId($id)
            ->locate();

        if (! $locator->getRecipient()) {
            $locator->throwNotFoundError();
        }

        return new RecipientThreadResource(
            $locator->getRecipient(),
            $locator->getThread()
        );
    }
}
