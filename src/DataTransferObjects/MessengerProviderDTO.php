<?php

namespace RTippin\Messenger\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;

class MessengerProviderDTO implements Arrayable
{
    /**
     * @var string
     */
    public string $class;

    /**
     * @var string
     */
    public string $alias;

    /**
     * @var string
     */
    public string $morphClass;

    /**
     * @var bool
     */
    public bool $searchable;

    /**
     * @var bool
     */
    public bool $friendable;

    /**
     * @var bool
     */
    public bool $hasDevices;

    /**
     * @var string
     */
    public string $defaultAvatarPath;

    /**
     * @var array
     */
    public array $cantMessageFirst;

    /**
     * @var array
     */
    public array $cantSearch;

    /**
     * @var array
     */
    public array $cantFriend;

    /**
     * @param  string  $provider
     *
     * @see MessengerProvider
     */
    public function __construct(string $provider)
    {
        /** @var MessengerProvider $provider */
        $settings = $provider::getProviderSettings();

        $this->class = $provider;
        $this->alias = $settings['alias'] ?? Str::snake(class_basename($provider));
        $this->morphClass = $this->determineProviderMorphClass($provider);
        $this->searchable = $this->determineIfSearchable($provider, $settings);
        $this->friendable = $settings['friendable'] ?? true;
        $this->hasDevices = $settings['devices'] ?? true;
        $this->defaultAvatarPath = $settings['default_avatar'] ?? Messenger::getDefaultNotFoundImage();
        $this->cantMessageFirst = $settings['cant_message_first'] ?? [];
        $this->cantSearch = $settings['cant_search'] ?? [];
        $this->cantFriend = $settings['cant_friend'] ?? [];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'alias' => $this->alias,
            'morph_class' => $this->morphClass,
            'searchable' => $this->searchable,
            'friendable' => $this->friendable,
            'devices' => $this->hasDevices,
            'default_avatar' => $this->defaultAvatarPath,
            'cant_message_first' => $this->cantMessageFirst,
            'cant_search' => $this->cantSearch,
            'cant_friend' => $this->cantFriend,
        ];
    }

    /**
     * Get the classname/alias for polymorphic relations.
     *
     * @param  string  $provider
     * @return string
     */
    private function determineProviderMorphClass(string $provider): string
    {
        $morphMap = Relation::morphMap();

        if (! empty($morphMap) && in_array($provider, $morphMap)) {
            return array_search($provider, $morphMap, true);
        }

        return $provider;
    }

    /**
     * @param  string  $provider
     * @param  array  $settings
     * @return bool
     */
    private function determineIfSearchable(string $provider, array $settings): bool
    {
        return method_exists($provider, 'getProviderSearchableBuilder')
            && ($settings['searchable'] ?? true);
    }
}
