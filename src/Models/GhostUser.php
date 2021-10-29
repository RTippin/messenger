<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Support\Helpers;

class GhostUser extends Model
{
    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var string
     */
    private string $name = 'Ghost Profile';

    /**
     * @var bool
     */
    private bool $ghostBot = false;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->setKeyType(Messenger::shouldUseUuids() ? 'string' : 'int');

        $this->setIncrementing(! Messenger::shouldUseUuids());

        parent::__construct($attributes);

        if (Messenger::shouldUseUuids()) {
            $this->{$this->getKeyName()} = Str::orderedUuid()->toString();
        } else {
            $this->{$this->getKeyName()} = 1234;
        }
    }

    /**
     * Use the GhostUser as a Ghost Bot.
     */
    public function ghostBot(): self
    {
        $this->ghostBot = true;

        $this->name = 'Bot';

        return $this;
    }

    /**
     * Get the provider settings and alias override, if set.
     *
     * @return array
     */
    public static function getProviderSettings(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getProviderAvatarColumn(): string
    {
        return 'none';
    }

    /**
     * @return string
     */
    public function getProviderLastActiveColumn(): string
    {
        return 'none';
    }

    /**
     * @return string|null
     */
    public function getProviderProfileRoute(): ?string
    {
        return null;
    }

    /**
     * @param  string  $size
     * @return string|null
     */
    public function getProviderAvatarRoute(string $size = 'sm'): ?string
    {
        return Helpers::route('assets.messenger.provider.avatar.render',
            [
                'alias' => $this->ghostBot ? 'bot' : 'ghost',
                'id' => $this->getKey(),
                'size' => $size,
                'image' => 'default.png',
            ]
        );
    }

    /**
     * @return int
     */
    public function getProviderOnlineStatus(): int
    {
        return MessengerProvider::OFFLINE;
    }
}
