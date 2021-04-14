<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Database\Factories\InviteFactory;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Support\Helpers;
use RTippin\Messenger\Traits\Uuids;

/**
 * App\Models\Messages\Invite.
 *
 * @property string $id
 * @property string $thread_id
 * @property string $owner_type
 * @property string|int $owner_id
 * @property string $code
 * @property int $max_use
 * @property int $uses
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Model|MessengerProvider $owner
 * @property-read \RTippin\Messenger\Models\Thread $thread
 * @method static \Illuminate\Database\Query\Builder|\RTippin\Messenger\Models\Invite onlyTrashed()
 * @method static \Illuminate\Database\Query\Builder|\RTippin\Messenger\Models\Invite withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\RTippin\Messenger\Models\Invite withoutTrashed()
 * @method static Builder|Message valid()
 * @method static Builder|Message invalid()
 * @method increment($column, $amount = 1, array $extra = [])
 * @mixin Model|\Eloquent
 */
class Invite extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'thread_invites';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    public $keyType = 'string';

    /**
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $dates = ['expires_at'];

    /**
     * @var array
     */
    protected $casts = [
        'max_use' => 'integer',
        'uses' => 'integer',
    ];

    /**
     * @return MorphTo|MessengerProvider
     */
    public function owner()
    {
        return $this->morphTo()->withDefault(function () {
            return Messenger::getGhostProvider();
        });
    }

    /**
     * @return BelongsTo|Thread
     */
    public function thread()
    {
        return $this->belongsTo(
            Thread::class,
            'thread_id',
            'id'
        );
    }

    /**
     * Scope valid invites that have not expired or reached max use.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('max_use', '=', 0)
            ->orWhere('thread_invites.uses', '<', $q->raw('thread_invites.max_use'))
        )->where(fn (Builder $q) => $q->whereNull('expires_at')
            ->orWhere('expires_at', '>', now()));
    }

    /**
     * Scope invalid invites that are not yet deleted but are expired / past max use.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInvalid(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now())
            ->orWhere(fn (Builder $q) => $q->where('max_use', '!=', 0)
                ->where('thread_invites.uses', '>=', $q->raw('thread_invites.max_use'))
            );
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->thread
            && $this->thread->invitations
            && ! $this->thread->lockout
            && (is_null($this->expires_at)
                || $this->expires_at > now())
            && ($this->max_use === 0
                || $this->max_use > $this->uses);
    }

    /**
     * @return string|null
     */
    public function getInvitationRoute(): ?string
    {
        return Helpers::Route('messenger.invites.join',
            [
                'invite' => $this->code,
            ],
            true
        );
    }

    /**
     * @param string $size
     * @param bool $api
     * @return string|null
     */
    public function getInvitationAvatarRoute(string $size = 'sm', $api = false): ?string
    {
        return Helpers::Route(($api ? 'api.' : '').'messenger.invites.avatar.render',
            [
                'invite' => $this->code,
                'size' => $size,
                'image' => $this->thread->image,
            ]
        );
    }

    /**
     * @param bool $api
     * @return array
     */
    public function inviteAvatar($api = false): array
    {
        return [
            'sm' => $this->getInvitationAvatarRoute('sm', $api),
            'md' => $this->getInvitationAvatarRoute('md', $api),
            'lg' => $this->getInvitationAvatarRoute('lg', $api),
        ];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return InviteFactory::new();
    }
}
