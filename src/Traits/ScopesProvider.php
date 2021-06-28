<?php

namespace RTippin\Messenger\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Models\Thread;

/**
 * @method static Builder|Call|CallParticipant|Friend|Invite|Message|MessageReaction|Messenger|Participant|PendingFriend|SentFriend|Thread forProvider(MessengerProvider $provider, string $morph = 'owner')
 * @method static Builder|Call|CallParticipant|Friend|Invite|Message|MessageReaction|Messenger|Participant|PendingFriend|SentFriend|Thread forProviderWithModel(Model $model, string $modelKey = 'owner', string $morphKey = 'owner')
 * @method static Builder|Call|CallParticipant|Friend|Invite|Message|MessageReaction|Messenger|Participant|PendingFriend|SentFriend|Thread notProvider(MessengerProvider $provider, string $morph = 'owner')
 * @method static Builder|Call|CallParticipant|Friend|Invite|Message|MessageReaction|Messenger|Participant|PendingFriend|SentFriend|Thread notProviderWithModel(Model $model, string $modelKey = 'owner', string $morphKey = 'owner')
 * @method static Builder|Call|CallParticipant|Friend|Invite|Message|MessageReaction|Messenger|Participant|PendingFriend|SentFriend|Thread hasProvider(string $relation, MessengerProvider $provider)
 */
trait ScopesProvider
{
    /**
     * Scope a query for belonging to the given provider.
     *
     * @param Builder $query
     * @param MessengerProvider $provider
     * @param string $morph
     * @param bool $not
     * @return Builder
     */
    public function scopeForProvider(Builder $query,
                                     MessengerProvider $provider,
                                     string $morph = 'owner',
                                     bool $not = false): Builder
    {
        return $query->where(
            $this->concatBuilder($morph),
            $not ? '!=' : '=',
            $provider->getMorphClass().$provider->getKey()
        );
    }

    /**
     * Scope a query not belonging to the given provider.
     *
     * @param Builder $query
     * @param MessengerProvider $provider
     * @param string $morph
     * @return Builder
     */
    public function scopeNotProvider(Builder $query,
                                     MessengerProvider $provider,
                                     string $morph = 'owner'): Builder
    {
        return $this->scopeForProvider($query, $provider, $morph, true);
    }

    /**
     * Scope a query for belonging to the given provider.
     *
     * @param Builder $query
     * @param string $relation
     * @param MessengerProvider $provider
     * @param string $morph
     * @return Builder
     */
    public function scopeHasProvider(Builder $query,
                                     string $relation,
                                     MessengerProvider $provider,
                                     string $morph = 'owner'): Builder
    {
        return $query->whereHas($relation, fn (Builder $query) => $this->scopeForProvider($query, $provider, $morph));
    }

//    public function scopeHasProviderTest(Builder $query,
//                                         string $parent,
//                                         string $joins,
//                                         MessengerProvider $provider,
//                                         string $morph = 'owner'): Builder
//    {
//        $singularParent = Str::singular($parent);
//
//        return $query->addSelect("$parent.*")
//            ->join($joins, "$parent.id", '=', "$joins.{$singularParent}_id")
//            ->where($this->concatBuilder($morph), '=', $provider->getMorphClass().$provider->getKey())
//            ->whereNull("$joins.deleted_at");
//
//    }

    /**
     * Scope a query for belonging to the given model using relation keys present.
     *
     * @param Builder $query
     * @param Model $model
     * @param string $modelKey
     * @param string $morphKey
     * @param bool $not
     * @return Builder
     */
    public function scopeForProviderWithModel(Builder $query,
                                              Model $model,
                                              string $modelKey = 'owner',
                                              string $morphKey = 'owner',
                                              bool $not = false): Builder
    {
        return $query->where(
            $this->concatBuilder($morphKey),
            $not ? '!=' : '=',
            $model->{$modelKey.'_type'}.$model->{$modelKey.'_id'}
        );
    }

    /**
     * Scope a query not belonging to the given model using relation keys present.
     *
     * @param Builder $query
     * @param Model $model
     * @param string $modelKey
     * @param string $morphKey
     * @return Builder
     */
    public function scopeNotProviderWithModel(Builder $query,
                                              Model $model,
                                              string $modelKey = 'owner',
                                              string $morphKey = 'owner'): Builder
    {
        return $this->scopeForProviderWithModel($query, $model, $modelKey, $morphKey, true);
    }

    /**
     * @param string $morph
     * @return Expression
     */
    private function concatBuilder(string $morph): Expression
    {
        if (DB::getDriverName() === 'sqlite') {
            $query = "{$morph}_type || {$morph}_id";
        } else {
            $query = "CONCAT({$morph}_type, {$morph}_id)";
        }

        return new Expression($query);
    }
}
