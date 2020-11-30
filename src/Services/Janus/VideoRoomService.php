<?php

namespace RTippin\Messenger\Services\Janus;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VideoRoomService extends JanusServer
{
    /**
     * String name of janus plugin we attach to
     * https://janus.conf.meetecho.com/docs/videoroom.html
     */
    const PLUGIN = 'janus.plugin.videoroom';

    /**
     * @var mixed
     */
    private $PLUGIN_ADMIN_KEY;

    /**
     * VideoRoomService constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->PLUGIN_ADMIN_KEY = config('janus.video_room_secret');
    }

    /**
     * Create our initial session and handle for this plugin
     * @return $this
     */
    private function setup()
    {
        $this->connect()->attach(self::PLUGIN);

        return $this;
    }

    /**
     * Check if the plugin response we expect is valid
     * @param string $success
     * @return bool
     */
    private function isValidPluginResponse($success = 'success')
    {
        return isset($this->plugin_response['videoroom']) && $this->plugin_response['videoroom'] === $success;
    }

    /**
     * List all Video Rooms we have in this janus server
     * @return bool|array
     */
    public function list()
    {
        $this->setup()->sendMessage([
            'request' => 'list'
        ])->disconnect();

        if($this->isValidPluginResponse()) return $this->plugin_response['list'];

        $this->logPluginError('list');

        return false;
    }

    /**
     * Check if janus has a video room with ID
     * @param $room
     * @return bool
     */
    public function exists($room)
    {
        $this->setup()->sendMessage([
            'request' => 'exists',
            'room' => $room
        ])->disconnect();

        if($this->isValidPluginResponse()) return $this->plugin_response['exists'];

        $this->logPluginError('exists');

        return false;
    }

    /**
     * Create a new video room, set flags to override defaults
     * ex: ['publishers' => 10, 'bitrate' => 1024000]
     * @param array|null $params
     * @param bool $pin
     * @param bool $secret
     * @return array|bool
     */
    public function create(array $params = null, $pin = true, $secret = true)
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
            'admin_key' => $this->PLUGIN_ADMIN_KEY
        ];

        if($params && count($params))
        {
            foreach ($params as $key => $value){
                $create[$key] = $value;
            }
        }

        $this->setup()->sendMessage($create)->disconnect();

        if($this->isValidPluginResponse('created'))
        {
            return [
                'room' => $this->plugin_response['room'],
                'pin' => $pin ? $make_pin : null,
                'secret' => $secret ? $make_secret : null
            ];
        }

        $this->logPluginError( 'create');

        return false;
    }

    /**
     * Edit params of a given video room
     * @param $room
     * @param array|null $params
     * @return bool
     */
    public function edit($room, array $params = null)
    {
        $edit = [
            'request' => 'edit',
            'room' => $room,
            'secret' => ''
        ];

        if($params && count($params))
        {
            foreach ($params as $key => $value){
                $edit[$key] = $value;
            }
        }

        $this->setup()->sendMessage($edit)->disconnect();

        if($this->isValidPluginResponse('edited')) return true;

        $this->logPluginError( 'edit');

        return false;
    }

    /**
     * Configure whether to check tokens or add/remove people who can join a room
     * @param $room
     * @param $action
     * @param array $allowed
     * @param null $secret
     * @return array|bool
     */
    public function allowed($room, $action, array $allowed = [], $secret = null)
    {
        $this->setup()->sendMessage([
            'request' => 'allowed',
            'room' => $room,
            'action' => $action,
            'secret' => isset($secret) ? $secret : '',
            'allowed' => $allowed
        ])->disconnect();

        if($this->isValidPluginResponse()) return $this->plugin_response;

        $this->logPluginError( 'allowed');

        return false;
    }

    /**
     * Kick a participant from a room using their private janus participant ID
     * @param $room
     * @param $participantID
     * @param null $secret
     * @return bool
     */
    public function kick($room, $participantID, $secret = null)
    {
        $this->setup()->sendMessage([
            'request' => 'kick',
            'room' => $room,
            'secret' => isset($secret) ? $secret : '',
            'id' => $participantID
        ])->disconnect();

        if($this->isValidPluginResponse()) return true;

        $this->logPluginError( 'kick');

        return false;
    }

    /**
     * Get a list of the participants in a specific room
     * @param $room
     * @return bool|mixed
     */
    public function listParticipants($room)
    {
        $this->setup()->sendMessage([
            'request' => 'listparticipants',
            'room' => $room
        ])->disconnect();

        if($this->isValidPluginResponse('participants')) return $this->plugin_response['participants'];

        $this->logPluginError( 'listparticipants');

        return false;
    }

    /**
     * Tell janus to destroy a room given the room ID and secret
     * @param null $room
     * @param null $secret
     * @return bool
     */
    public function destroy($room = null, $secret = null)
    {
        $this->setup()->sendMessage([
            'request' => 'destroy',
            'room' => $room,
            'secret' => $secret ? $secret : ''
        ])->disconnect();

        if($this->isValidPluginResponse('destroyed')) return true;

        $this->logPluginError('destroy', [
            'room' => $room,
            'secret' => $secret
        ]);

        return false;
    }

    /**
     * Tell janus to destroy a collection of rooms in sequence within one session
     * Example: collect([['room' => 12345,'secret' => 'secret'],['room' => 67891,'secret' => 'secret2']]);
     * @param Collection|null $bulk
     * @return bool
     */
    public function destroyBulk(Collection $bulk = null)
    {
        if(!$bulk || $bulk && !$bulk->count()) return false;

        $this->setup();

        $bulk->each(function($destroy){
            $this->sendMessage([
                'request' => 'destroy',
                'room' => $destroy['room'],
                'secret' => isset($destroy['secret']) ? $destroy['secret'] : ''
            ]);

            if(!$this->isValidPluginResponse('destroyed'))
            {
                $this->logPluginError('destroyBulk', [
                    'room' => $destroy['room'],
                    'secret' => $destroy['secret']
                ]);
            }

        });

        $this->disconnect();

        return true;
    }

}
