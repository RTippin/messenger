<?php

namespace RTippin\Messenger\Services\Janus;

use Illuminate\Support\Str;

class VideoRoomService
{
    /**
     * String name of janus plugin we attach to
     * https://janus.conf.meetecho.com/docs/videoroom.html.
     */
    const PLUGIN = 'janus.plugin.videoroom';

    /**
     * @var string
     */
    private string $PLUGIN_ADMIN_KEY;

    /**
     * @var JanusServer
     */
    private JanusServer $janus;

    /**
     * VideoRoomService constructor.
     *
     * @param JanusServer $janus
     */
    public function __construct(JanusServer $janus)
    {
        $this->janus = $janus;
        $this->PLUGIN_ADMIN_KEY = config('janus.video_room_secret');
    }

    /**
     * Create our initial session and handle for this plugin.
     *
     * @return JanusServer
     */
    private function setup(): JanusServer
    {
        return $this->janus->connect()->attach(self::PLUGIN);
    }

    /**
     * Check if the plugin response we expect is valid.
     *
     * @param string $success
     * @return bool
     */
    private function isValidPluginResponse(string $success = 'success'): bool
    {
        return isset($this->janus->getPluginResponse()['videoroom'])
            && $this->janus->getPluginResponse()['videoroom'] === $success;
    }

    /**
     * List all Video Rooms we have in this janus server.
     *
     * @return array
     */
    public function list(): array
    {
        $this->setup()->sendMessage([
            'request' => 'list',
        ])->disconnect();

        if ($this->isValidPluginResponse()) {
            return $this->janus->getPluginResponse()['list'];
        }

        $this->janus->logPluginError('list');

        return [];
    }

    /**
     * Check if janus has a video room with ID.
     *
     * @param int $room
     * @return bool
     */
    public function exists(int $room): bool
    {
        $this->setup()->sendMessage([
            'request' => 'exists',
            'room' => $room,
        ])->disconnect();

        if ($this->isValidPluginResponse()) {
            return $this->janus->getPluginResponse()['exists'];
        }

        $this->janus->logPluginError('exists');

        return false;
    }

    /**
     * Create a new video room, set flags to override defaults
     * ex: ['publishers' => 10, 'bitrate' => 1024000].
     *
     * @param array $params
     * @param bool $pin
     * @param bool $secret
     * @return array
     */
    public function create(array $params = [],
                           bool $pin = true,
                           bool $secret = true): array
    {
        $make_pin = $pin ? Str::random(6) : '';

        $make_secret = $secret ? Str::random(12) : '';

        $create = [
            'request' => 'create',
            'publishers' => 2,
            'description' => Str::random(10),
            'audiolevel_event' => true,
            'audiolevel_ext' => true,
            'audio_active_packets' => 50,
            'audio_level_average' => 25,
            'notify_joining' => true,
            'bitrate' => 600000,
            'pin' => $make_pin,
            'secret' => $make_secret,
            'admin_key' => $this->PLUGIN_ADMIN_KEY,
        ];

        if (count($params)) {
            foreach ($params as $key => $value) {
                $create[$key] = $value;
            }
        }

        $this->setup()->sendMessage($create)->disconnect();

        if ($this->isValidPluginResponse('created')) {
            return [
                'room' => $this->janus->getPluginResponse()['room'],
                'pin' => $pin ? $make_pin : null,
                'secret' => $secret ? $make_secret : null,
            ];
        }

        $this->janus->logPluginError('create');

        return [];
    }

    /**
     * Edit params of a given video room.
     *
     * @param int $room
     * @param array $params
     * @return bool
     */
    public function edit(int $room, array $params = []): bool
    {
        $edit = [
            'request' => 'edit',
            'room' => $room,
            'secret' => '',
        ];

        if (count($params)) {
            foreach ($params as $key => $value) {
                $edit[$key] = $value;
            }
        }

        $this->setup()->sendMessage($edit)->disconnect();

        if ($this->isValidPluginResponse('edited')) {
            return true;
        }

        $this->janus->logPluginError('edit');

        return false;
    }

    /**
     * Configure whether to check tokens or add/remove people who can join a room.
     *
     * @param int $room
     * @param string $action
     * @param array $allowed
     * @param string|null $secret
     * @return array
     */
    public function allowed(int $room,
                            string $action,
                            array $allowed = [],
                            string $secret = null): array
    {
        $this->setup()->sendMessage([
            'request' => 'allowed',
            'room' => $room,
            'action' => $action,
            'secret' => $secret ?? '',
            'allowed' => $allowed,
        ])
        ->disconnect();

        if ($this->isValidPluginResponse()) {
            return $this->janus->getPluginResponse();
        }

        $this->janus->logPluginError('allowed');

        return [];
    }

    /**
     * Kick a participant from a room using their private janus participant ID.
     *
     * @param int $room
     * @param int $participantID
     * @param string|null $secret
     * @return bool
     */
    public function kick(int $room,
                         int $participantID,
                         string $secret = null): bool
    {
        $this->setup()->sendMessage([
            'request' => 'kick',
            'room' => $room,
            'secret' => $secret ?? '',
            'id' => $participantID,
        ])->disconnect();

        if ($this->isValidPluginResponse()) {
            return true;
        }

        $this->janus->logPluginError('kick');

        return false;
    }

    /**
     * Get a list of the participants in a specific room.
     * @param int $room
     * @return array
     */
    public function listParticipants(int $room): array
    {
        $this->setup()->sendMessage([
            'request' => 'listparticipants',
            'room' => $room,
        ])->disconnect();

        if ($this->isValidPluginResponse('participants')) {
            return $this->janus->getPluginResponse()['participants'];
        }

        $this->janus->logPluginError('listparticipants');

        return [];
    }

    /**
     * Tell janus to destroy a room given the room ID and secret.
     *
     * @param int|null $room
     * @param string|null $secret
     * @return bool
     */
    public function destroy(int $room = null, ?string $secret = null): bool
    {
        $this->setup()->sendMessage([
            'request' => 'destroy',
            'room' => $room,
            'secret' => $secret ?: '',
        ])->disconnect();

        if ($this->isValidPluginResponse('destroyed')) {
            return true;
        }

        $this->janus->logPluginError('destroy', [
            'room' => $room,
            'secret' => $secret,
        ]);

        return false;
    }
}
