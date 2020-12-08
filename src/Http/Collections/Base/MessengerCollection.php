<?php

namespace RTippin\Messenger\Http\Collections\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\GroupThreadRepository;
use RTippin\Messenger\Repositories\PrivateThreadRepository;
use RTippin\Messenger\Repositories\ThreadRepository;

abstract class MessengerCollection extends ResourceCollection
{
    /**
     * @var Thread
     */
    protected Thread $thread;

    /**
     * @var bool
     */
    protected bool $paginate;

    /**
     * @var null|string
     */
    protected ?string $pageId;

    /**
     * @var string
     */
    protected string $collectionType;

    /**
     * Transform the collection to resources, safe guarding against
     * breaking the entire collection should one resource fail.
     *
     * @return array
     */
    protected function safeTransformer(): array
    {
        return $this->collection
            ->map(
                fn ($resource) => $this->makeResource($resource)
            )
            ->reject(
                fn ($resource) => is_null($resource)
            )
            ->toArray();
    }

    /**
     * We go ahead and attempt to create and resolve each individual
     * resource, returning null should one fail.
     *
     * @param mixed $resource
     * @return array|null
     */
    abstract protected function makeResource($resource): ?array;

    /**
     * Total count we have in the database of each resource.
     *
     * @return int
     */
    protected function grandTotal(): int
    {
        switch ($this->collectionType) {
            case 'threads':
                return app(ThreadRepository::class)
                    ->getProviderThreadsBuilder()
                    ->count();
            case 'groups':
                return app(GroupThreadRepository::class)
                    ->getProviderGroupThreadsBuilder()
                    ->count();
            case 'privates':
                return app(PrivateThreadRepository::class)
                    ->getProviderPrivateThreadsBuilder()
                    ->count();
            case 'participants':
                return $this->thread->participants()
                    ->count();
            case 'messages':
                return $this->thread->messages()
                    ->count();
            case 'logs':
                return $this->thread->messages()
                    ->system()
                    ->count();
            case 'images':
                return $this->thread->messages()
                    ->image()
                    ->count();
            case 'documents':
                return $this->thread->messages()
                    ->document()
                    ->count();
            case 'calls':
                return $this->thread->calls()
                    ->videoCall()
                    ->count();
            default:
                return 0;
        }
    }

    /**
     * Per page counts set in our config.
     *
     * @return int
     */
    protected function perPageConfig(): int
    {
        if ($this->paginate === true) {
            switch ($this->collectionType) {
                case 'threads':
                case 'groups':
                case 'privates':
                    return messenger()->getThreadsPageCount();
                case 'participants':
                    return messenger()->getParticipantsPageCount();
                case 'messages':
                case 'logs':
                case 'images':
                case 'documents':
                    return messenger()->getMessagesPageCount();
                case 'calls':
                    return messenger()->getCallsPageCount();
                default:
                    return 25;
            }
        }

        switch ($this->collectionType) {
            case 'threads':
            case 'groups':
            case 'privates':
                return messenger()->getThreadsIndexCount();
            case 'participants':
                return messenger()->getParticipantsIndexCount();
            case 'messages':
            case 'logs':
            case 'images':
            case 'documents':
                return messenger()->getMessagesIndexCount();
            case 'calls':
                return messenger()->getCallsIndexCount();
            default:
                return 25;
        }
    }

    /**
     * @return bool
     */
    protected function isIndex(): bool
    {
        return $this->paginate === false;
    }

    /**
     * @return string|int|null
     */
    protected function nextPageId()
    {
        return $this->collection->count() && ! $this->isFinalPage()
            ? $this->collection->last()->id
            : null;
    }

    /**
     * @return string|null
     */
    protected function nextPageLink(): ?string
    {
        if ($this->nextPageId()) {
            switch ($this->collectionType) {
                case 'threads':
                    return messengerRoute('api.messenger.threads.page', $this->nextPageId());
                case 'groups':
                    return messengerRoute('api.messenger.groups.page', $this->nextPageId());
                case 'privates':
                    return messengerRoute('api.messenger.privates.page', $this->nextPageId());
                case 'participants':
                    return messengerRoute('api.messenger.threads.participants.page',
                        [
                            'thread' => $this->thread->id,
                            'participant' => $this->nextPageId(),
                        ]
                    );
                case 'messages':
                    return messengerRoute('api.messenger.threads.messages.page',
                        [
                            'thread' => $this->thread->id,
                            'message' => $this->nextPageId(),
                        ]
                    );
                case 'calls':
                    return messengerRoute('api.messenger.threads.calls.page',
                        [
                            'thread' => $this->thread->id,
                            'call' => $this->nextPageId(),
                        ]
                    );
                case 'logs':
                    return messengerRoute('api.messenger.threads.logs.page',
                        [
                            'thread' => $this->thread->id,
                            'log' => $this->nextPageId(),
                        ]
                    );
                case 'images':
                    return messengerRoute('api.messenger.threads.images.page',
                        [
                            'thread' => $this->thread->id,
                            'image' => $this->nextPageId(),
                        ]
                    );
                case 'documents':
                    return messengerRoute('api.messenger.threads.documents.page',
                        [
                            'thread' => $this->thread->id,
                            'document' => $this->nextPageId(),
                        ]
                    );
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function isFinalPage(): bool
    {
        if ($this->isIndex()) {
            switch ($this->collectionType) {
                case 'threads':
                case 'groups':
                case 'privates':
                    return $this->collection->count() < messenger()->getThreadsIndexCount();
                case 'participants':
                    return $this->collection->count() < messenger()->getParticipantsIndexCount();
                case 'messages':
                case 'logs':
                case 'images':
                case 'documents':
                    return $this->collection->count() < messenger()->getMessagesIndexCount();
                case 'calls':
                    return $this->collection->count() < messenger()->getCallsIndexCount();
            }
        }

        if (! $this->collection->count()
            || $this->collection->count() < $this->perPageConfig()) {
            return true;
        }

        /** @var Model|mixed|null $model */
        $model = null;

        switch ($this->collectionType) {
            case 'threads':
                $model = app(ThreadRepository::class)->getProviderOldestThread();
            break;
            case 'groups':
                $model = app(GroupThreadRepository::class)->getProviderOldestGroupThread();
            break;
            case 'privates':
                $model = app(PrivateThreadRepository::class)->getProviderOldestPrivateThread();
            break;
            case 'participants':
                $model = $this->thread->participants()->latest()->first();
            break;
            case 'messages':
                $model = $this->thread->messages()->oldest()->first();
            break;
            case 'calls':
                $model = $this->thread->calls()->videoCall()->oldest()->first();
            break;
            case 'logs':
                $model = $this->thread->messages()->system()->oldest()->first();
            break;
            case 'images':
                $model = $this->thread->messages()->image()->oldest()->first();
            break;
            case 'documents':
                $model = $this->thread->messages()->document()->oldest()->first();
            break;
        }

        return $this->collection->firstWhere('id', optional($model)->getKey())
            ? true
            : false;
    }
}
