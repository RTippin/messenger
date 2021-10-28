<?php

namespace RTippin\Messenger\Http\Collections\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\GroupThreadRepository;
use RTippin\Messenger\Repositories\PrivateThreadRepository;
use RTippin\Messenger\Repositories\ThreadRepository;
use RTippin\Messenger\Support\Helpers;

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
            ->map(fn ($resource) => $this->makeResource($resource))
            ->filter()
            ->toArray();
    }

    /**
     * We go ahead and attempt to create and resolve each individual
     * resource, returning null should one fail.
     *
     * @param  mixed  $resource
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
                return Thread::hasProvider(Messenger::getProvider())->count();
            case 'groups':
                return Thread::hasProvider(Messenger::getProvider())->group()->count();
            case 'privates':
                return Thread::hasProvider(Messenger::getProvider())->private()->count();
            case 'participants':
                return $this->thread->participants()->count();
            case 'messages':
                return $this->thread->messages()->count();
            case 'logs':
                return $this->thread->messages()->system()->count();
            case 'images':
                return $this->thread->messages()->image()->count();
            case 'documents':
                return $this->thread->messages()->document()->count();
            case 'audio':
                return $this->thread->messages()->audio()->count();
            case 'videos':
                return $this->thread->messages()->video()->count();
            case 'calls':
                return $this->thread->calls()->videoCall()->count();
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
                    return Messenger::getThreadsPageCount();
                case 'participants':
                    return Messenger::getParticipantsPageCount();
                case 'messages':
                case 'logs':
                case 'images':
                case 'audio':
                case 'documents':
                case 'videos':
                    return Messenger::getMessagesPageCount();
                case 'calls':
                    return Messenger::getCallsPageCount();
                default:
                    return 25;
            }
        }

        switch ($this->collectionType) {
            case 'threads':
            case 'groups':
            case 'privates':
                return Messenger::getThreadsIndexCount();
            case 'participants':
                return Messenger::getParticipantsIndexCount();
            case 'messages':
            case 'logs':
            case 'images':
            case 'audio':
            case 'documents':
            case 'videos':
                return Messenger::getMessagesIndexCount();
            case 'calls':
                return Messenger::getCallsIndexCount();
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
                    return Helpers::route('api.messenger.threads.page', $this->nextPageId());
                case 'groups':
                    return Helpers::route('api.messenger.groups.page', $this->nextPageId());
                case 'privates':
                    return Helpers::route('api.messenger.privates.page', $this->nextPageId());
                case 'participants':
                    return Helpers::route('api.messenger.threads.participants.page',
                        [
                            'thread' => $this->thread->id,
                            'participant' => $this->nextPageId(),
                        ]
                    );
                case 'messages':
                    return Helpers::route('api.messenger.threads.messages.page',
                        [
                            'thread' => $this->thread->id,
                            'message' => $this->nextPageId(),
                        ]
                    );
                case 'calls':
                    return Helpers::route('api.messenger.threads.calls.page',
                        [
                            'thread' => $this->thread->id,
                            'call' => $this->nextPageId(),
                        ]
                    );
                case 'logs':
                    return Helpers::route('api.messenger.threads.logs.page',
                        [
                            'thread' => $this->thread->id,
                            'log' => $this->nextPageId(),
                        ]
                    );
                case 'images':
                    return Helpers::route('api.messenger.threads.images.page',
                        [
                            'thread' => $this->thread->id,
                            'image' => $this->nextPageId(),
                        ]
                    );
                case 'audio':
                    return Helpers::route('api.messenger.threads.audio.page',
                        [
                            'thread' => $this->thread->id,
                            'audio' => $this->nextPageId(),
                        ]
                    );
                case 'documents':
                    return Helpers::route('api.messenger.threads.documents.page',
                        [
                            'thread' => $this->thread->id,
                            'document' => $this->nextPageId(),
                        ]
                    );
                case 'videos':
                    return Helpers::route('api.messenger.threads.videos.page',
                        [
                            'thread' => $this->thread->id,
                            'video' => $this->nextPageId(),
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
                    return $this->collection->count() < Messenger::getThreadsIndexCount();
                case 'participants':
                    return $this->collection->count() < Messenger::getParticipantsIndexCount();
                case 'messages':
                case 'logs':
                case 'images':
                case 'audio':
                case 'documents':
                case 'videos':
                    return $this->collection->count() < Messenger::getMessagesIndexCount();
                case 'calls':
                    return $this->collection->count() < Messenger::getCallsIndexCount();
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
            case 'audio':
                $model = $this->thread->messages()->audio()->oldest()->first();
            break;
            case 'documents':
                $model = $this->thread->messages()->document()->oldest()->first();
            break;
            case 'videos':
                $model = $this->thread->messages()->video()->oldest()->first();
            break;
        }

        return (bool) $this->collection->firstWhere('id', optional($model)->getKey());
    }
}
