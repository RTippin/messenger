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
use RTippin\Messenger\Database\Factories\MessageFactory;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Traits\Uuids;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

/**
 * App\Models\Messages\Message.
 *
 * @property string $id
 * @property string $thread_id
 * @property string $owner_type
 * @property string $owner_id
 * @property int $type
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \RTippin\Messenger\Models\Thread $thread
 * @property-read Model|MessengerProvider $owner
 * @method static \Illuminate\Database\Eloquent\Builder|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newQuery()
 * @method static \Illuminate\Database\Query\Builder|Message onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereOwnerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereThreadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Message withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Message withoutTrashed()
 * @mixin Model|\Eloquent
 * @method static Builder|Message text()
 * @method static Builder|Message document()
 * @method static Builder|Message image()
 * @method static Builder|Message system()
 * @method static Builder|Message nonSystem()
 */
class Message extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasEagerLimit;
    use Uuids;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var null|string
     */
    public $temporaryId = null;

    /**
     * @var string
     */
    public $keyType = 'string';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'messages';

    /**
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

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
     * @return MorphTo|MessengerProvider
     */
    public function owner()
    {
        return $this->morphTo()->withDefault(function () {
            return messenger()->getGhostProvider();
        });
    }

    /**
     * Scope a query for only regular text based messages.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeText(Builder $query): Builder
    {
        return $query->where('type', '=', 0);
    }

    /**
     * Scope a query for anything but system messages.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeNonSystem(Builder $query): Builder
    {
        return $query->whereIn('type', [0, 1, 2]);
    }

    /**
     * Scope a query for only system messages.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->whereNotIn('type', [0, 1, 2]);
    }

    /**
     * Scope a query for only image messages.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeImage(Builder $query): Builder
    {
        return $query->where('type', '=', 1);
    }

    /**
     * Scope a query for only image messages.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeDocument(Builder $query): Builder
    {
        return $query->where('type', '=', 2);
    }

    /**
     * @return string
     */
    public function getStorageDisk(): string
    {
        return messenger()->getThreadStorage('disk');
    }

    /**
     * @return string
     */
    public function getStorageDirectory(): string
    {
        return messenger()->getThreadStorage('directory')."/{$this->thread_id}";
    }

    /**
     * @return string
     */
    public function getImagePath(): string
    {
        return "{$this->getStorageDirectory()}/images/{$this->body}";
    }

    /**
     * @return string
     */
    public function getDocumentPath(): string
    {
        return "{$this->getStorageDirectory()}/documents/{$this->body}";
    }

    /**
     * @return string
     */
    public function getTypeVerbose(): string
    {
        return Definitions::Message[$this->type];
    }

    /**
     * @param string $size
     * @param bool $api
     * @return string|null
     */
    public function getImageViewRoute(string $size = 'sm', $api = false): ?string
    {
        if (! $this->isImage()) {
            return null;
        }

        return messengerRoute(($api ? 'api.' : '').'messenger.threads.gallery.render',
            [
                'thread' => $this->thread_id,
                'message' => $this->id,
                'size' => $size,
                'image' => $this->body,
            ]
        );
    }

    /**
     * @param bool $api
     * @return string|null
     */
    public function getDocumentDownloadRoute($api = false): ?string
    {
        if (! $this->isDocument()) {
            return null;
        }

        return messengerRoute(($api ? 'api.' : '').'messenger.threads.files.download',
            [
                'thread' => $this->thread_id,
                'message' => $this->id,
                'file' => $this->body,
            ]
        );
    }

    /**
     * @return bool
     */
    public function isSystemMessage(): bool
    {
        return ! in_array($this->type, [0, 1, 2]);
    }

    /**
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->type === 1;
    }

    /**
     * @return bool
     */
    public function isDocument(): bool
    {
        return $this->type === 2;
    }

    /**
     * @return bool
     */
    public function hasTemporaryId(): bool
    {
        return ! is_null($this->temporaryId);
    }

    /**
     * @return string|null
     */
    public function temporaryId(): ?string
    {
        return $this->temporaryId;
    }

    /**
     * @param string|null $id
     * @return Message
     */
    public function setTemporaryId($id = null): self
    {
        $this->temporaryId = (is_null($id) || empty($id))
            ? null
            : $id;

        return $this;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory()
    {
        return MessageFactory::new();
    }
}
