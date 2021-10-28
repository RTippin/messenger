<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\Ownerable;
use RTippin\Messenger\Database\Factories\InviteFactory;
use RTippin\Messenger\Support\Helpers;
use RTippin\Messenger\Traits\HasOwner;
use RTippin\Messenger\Traits\ScopesProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * @mixin Model|\Eloquent
 *
 * @property string $id
 * @property string $thread_id
 * @property string $code
 * @property int $max_use
 * @property int $uses
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Thread $thread
 *
 * @method static \Illuminate\Database\Query\Builder|Invite onlyTrashed()
 * @method static \Illuminate\Database\Query\Builder|Invite withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Invite withoutTrashed()
 * @method static Builder|Message valid()
 * @method static Builder|Message invalid()
 * @method static InviteFactory factory(...$parameters)
 * @method increment($column, $amount = 1, array $extra = [])
 */
class Invite extends Model implements Ownerable
{
    use HasFactory,
        HasOwner,
        ScopesProvider,
        SoftDeletes,
        Uuids;

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
    protected $casts = [
        'expires_at' => 'datetime',
        'max_use' => 'integer',
        'uses' => 'integer',
    ];

    /**
     * @return BelongsTo|Thread
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * Scope valid invites that have not expired or reached max use.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where(
            fn (Builder $q) => $q->where('max_use', '=', 0)
                ->orWhere('thread_invites.uses', '<', $q->raw('thread_invites.max_use'))
        )
            ->where(
                fn (Builder $q) => $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now())
            );
    }

    /**
     * Scope invalid invites that are not yet deleted but are expired / past max use.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeInvalid(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now())
            ->orWhere(
                fn (Builder $q) => $q->where('max_use', '!=', 0)
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
     * If you want a fully qualified web route to view join invite page,
     * define your own web route with name 'messenger.invites.join'
     * and with key parameter '{invite:code}'.
     * Example:
     * Route::get('join/{invite}', [ViewPortalController::class, 'showJoinWithInvite'])->name('messenger.invites.join');.
     *
     * @return string|null
     */
    public function getInvitationRoute(): ?string
    {
        return Helpers::route('messenger.invites.join',
            [
                'invite' => $this->code,
            ],
            true
        );
    }

    /**
     * @param  string  $size
     * @return string|null
     */
    public function getInvitationAvatarRoute(string $size = 'sm'): ?string
    {
        return Helpers::route('assets.messenger.invites.avatar.render',
            [
                'invite' => $this->code,
                'size' => $size,
                'image' => $this->thread->image ?: 'default.png',
            ]
        );
    }

    /**
     * @return array
     */
    public function inviteAvatar(): array
    {
        return [
            'sm' => $this->getInvitationAvatarRoute(),
            'md' => $this->getInvitationAvatarRoute('md'),
            'lg' => $this->getInvitationAvatarRoute('lg'),
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
