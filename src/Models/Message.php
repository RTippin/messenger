<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Database\Factories\MessageFactory;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Support\Definitions;
use RTippin\Messenger\Support\Helpers;
use RTippin\Messenger\Traits\ScopesProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * @property string $id
 * @property string $thread_id
 * @property string $owner_type
 * @property string|int $owner_id
 * @property int $type
 * @property string $body
 * @property string $reply_to_id
 * @property bool $edited
 * @property bool $reacted
 * @property bool $embeds
 * @property string|array|null $extra
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \RTippin\Messenger\Models\Thread $thread
 * @property-read Model|MessengerProvider $owner
 * @property-read \RTippin\Messenger\Models\MessageEdit $edits
 * @property-read \RTippin\Messenger\Models\MessageReaction $reactions
 * @property-read \RTippin\Messenger\Models\Message $replyTo
 * @method static \Illuminate\Database\Query\Builder|Message onlyTrashed()
 * @method static \Illuminate\Database\Query\Builder|Message withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Message withoutTrashed()
 * @mixin Model|\Eloquent
 * @method static Builder|Message text()
 * @method static Builder|Message document()
 * @method static Builder|Message image()
 * @method static Builder|Message audio()
 * @method static Builder|Message system()
 * @method static Builder|Message nonSystem()
 */
class Message extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    use ScopesProvider;

    /**
     * Message types that are not system messages.
     */
    const NonSystemTypes = [0, 1, 2, 3];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'messages';

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var null|string
     */
    public ?string $temporaryId = null;

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
        'type' => 'integer',
        'edited' => 'boolean',
        'reacted' => 'boolean',
        'embeds' => 'boolean',
        'extra' => 'array',
    ];

    /**
     * @return BelongsTo|Thread
     */
    public function thread(): BelongsTo
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
    public function owner(): MorphTo
    {
        return $this->morphTo()->withDefault(function () {
            return Messenger::getGhostProvider();
        });
    }

    /**
     * @return HasMany|MessageEdit|Collection
     */
    public function edits(): HasMany
    {
        return $this->hasMany(MessageEdit::class);
    }

    /**
     * @return HasMany|MessageReaction|Collection
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * @return HasOne
     */
    public function replyTo(): HasOne
    {
        return $this->hasOne(
            Message::class,
            'id',
            'reply_to_id'
        );
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
        return $query->whereIn('type', self::NonSystemTypes);
    }

    /**
     * Scope a query for only system messages.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->whereNotIn('type', self::NonSystemTypes);
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
     * Scope a query for only document messages.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeDocument(Builder $query): Builder
    {
        return $query->where('type', '=', 2);
    }

    /**
     * Scope a query for only document messages.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeAudio(Builder $query): Builder
    {
        return $query->where('type', '=', 3);
    }

    /**
     * @return string
     */
    public function getStorageDisk(): string
    {
        return Messenger::getThreadStorage('disk');
    }

    /**
     * @return string
     */
    public function getStorageDirectory(): string
    {
        return Messenger::getThreadStorage('directory')."/$this->thread_id";
    }

    /**
     * @return string
     */
    public function getImagePath(): string
    {
        return "{$this->getStorageDirectory()}/images/$this->body";
    }

    /**
     * @return string
     */
    public function getDocumentPath(): string
    {
        return "{$this->getStorageDirectory()}/documents/$this->body";
    }

    /**
     * @return string
     */
    public function getAudioPath(): string
    {
        return "{$this->getStorageDirectory()}/audio/$this->body";
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
    public function getImageViewRoute(string $size = 'sm', bool $api = false): ?string
    {
        if (! $this->isImage()) {
            return null;
        }

        return Helpers::Route(($api ? 'api.' : '').'messenger.threads.gallery.render',
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
    public function getDocumentDownloadRoute(bool $api = false): ?string
    {
        if (! $this->isDocument()) {
            return null;
        }

        return Helpers::Route(($api ? 'api.' : '').'messenger.threads.files.download',
            [
                'thread' => $this->thread_id,
                'message' => $this->id,
                'file' => $this->body,
            ]
        );
    }

    /**
     * @param bool $api
     * @return string|null
     */
    public function getAudioDownloadRoute(bool $api = false): ?string
    {
        if (! $this->isAudio()) {
            return null;
        }

        return Helpers::Route(($api ? 'api.' : '').'messenger.threads.audio.download',
            [
                'thread' => $this->thread_id,
                'message' => $this->id,
                'audio' => $this->body,
            ]
        );
    }

    /**
     * @return string|null
     */
    public function getEditHistoryRoute(): ?string
    {
        if (! $this->isEdited()) {
            return null;
        }

        return Helpers::Route('api.messenger.threads.messages.history',
            [
                'thread' => $this->thread_id,
                'message' => $this->id,
            ]
        );
    }

    /**
     * @return bool
     */
    public function isEdited(): bool
    {
        return $this->isText() && $this->edited;
    }

    /**
     * @return bool
     */
    public function isReacted(): bool
    {
        return ! $this->isSystemMessage() && $this->reacted;
    }

    /**
     * @return bool
     */
    public function isText(): bool
    {
        return $this->type === 0;
    }

    /**
     * @return bool
     */
    public function showEmbeds(): bool
    {
        return $this->isText() && $this->embeds;
    }

    /**
     * @return bool
     */
    public function isSystemMessage(): bool
    {
        return ! in_array($this->type, self::NonSystemTypes);
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
    public function isAudio(): bool
    {
        return $this->type === 3;
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
    public function setTemporaryId(?string $id = null): self
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
    protected static function newFactory(): Factory
    {
        return MessageFactory::new();
    }
}
