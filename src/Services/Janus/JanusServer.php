<?php

namespace RTippin\Messenger\Services\Janus;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class JanusServer
{
    /**
     * Janus Media Server REST interface
     * https://janus.conf.meetecho.com/docs/rest.html
     */

    /**
     * @var mixed
     */
    private $janus_server;

    /**
     * @var mixed
     */
    private $janus_admin_server;

    /**
     * @var mixed
     */
    private $api_secret;

    /**
     * @var mixed
     */
    protected $log_errors;

    /**
     * @var mixed
     */
    private $self_signed;

    /**
     * @var mixed
     */
    protected $debug;

    /**
     * @var null|integer|float
     */
    protected $ping_pong = null;

    /**
     * @var null|integer|float
     */
    protected $last_latency = null;

    /**
     * @var null|string
     */
    private $session_id = null;

    /**
     * @var null|string
     */
    private $handle_id = null;

    /**
     * @var null|string
     */
    protected $plugin = null;

    /**
     * @var array
     */
    protected $api_response = [];

    /**
     * @var array
     */
    protected $plugin_payload = [];

    /**
     * @var array
     */
    protected $plugin_response = [];

    /**
     * JanusServer constructor.
     */
    public function __construct()
    {
        $this->api_secret = config('janus.api_secret');
        $this->log_errors = config('janus.log_failures');
        $this->janus_server = config('janus.server_endpoint');
        $this->janus_admin_server = config('janus.server_admin_endpoint');
        $this->debug = config('janus.backend_debug');
        $this->self_signed = config('janus.backend_ssl');
    }

    /**
     * Log an API error if logging enabled
     * @param null $data
     * @param null $route
     */
    private function logApiError($data = null, $route = null)
    {
        if($this->log_errors) Log::warning('janus.api', [
            'payload' => $data,
            'route' => $route,
            'response' => $this->api_response
        ]);
    }

    /**
     * Log error from the loaded plugin method if logging enabled
     * @param string $action
     * @param null|array|string $extra
     */
    protected function logPluginError($action = '', $extra = null)
    {
        if($this->log_errors) Log::warning($this->plugin . ' - ' . $action, [
            'payload' => $this->plugin_payload,
            'response' => $this->api_response,
            'extra' => $extra
        ]);
    }

    /**
     * Retrieve the janus server instance details
     * @return array|bool|mixed
     */
    public function serverInfo()
    {
        $this->janusAPI(null, 'info', false, false);

        return $this->api_response;
    }

    /**
     * Ping janus to see if it is alive.
     * @return array|bool
     */
    public function serverPing()
    {
        $this->janusAPI([
            'janus' => 'ping',
            'transaction' => Str::random(12)
        ], null, true);

        if(isset($this->api_response['janus']) && $this->api_response['janus'] === 'pong')
        {
            return [
                'pong' => true,
                'latency' => $this->last_latency,
                'message' => $this->last_latency  . ' milliseconds'
            ];
        }

        return false;
    }

    /**
     * Set the Janus configs defaulted in the constructor and class properties
     * Use property name as key => value
     * @param array|null $config
     * @return $this
     */
    public function setConfig(array $config = null)
    {
        if($config && count($config))
        {
            foreach ($config as $key => $value){
                if(property_exists($this, $key)) $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * Set the configs in the constructor back to defaults
     * @return $this
     */
    public function resetConfig()
    {
        $this->api_secret = config('janus.api_secret');
        $this->log_errors = config('janus.log_failures');
        $this->janus_server = config('janus.server_endpoint');
        $this->janus_admin_server = config('janus.server_admin_endpoint');
        $this->debug = config('janus.backend_debug');
        $this->self_signed = config('janus.backend_ssl');

        return $this;
    }

    /**
     * Easily set debug at any part of a chain
     * @param bool $debug
     * @return $this
     */
    public function debug($debug = true)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Connect with janus to set the session ID for this cycle
     * @return $this
     */
    public function connect()
    {
        $this->janusAPI([
            'janus' => 'create',
            'transaction' => Str::random(12),
            'apisecret' => $this->api_secret
        ]);

        $this->session_id = isset($this->api_response['data']['id'])
            ? $this->api_response['data']['id']
            : null;

        return $this;
    }

    /**
     * Attach to the janus plugin to get a handle ID. All request
     * in this cycle will go to this plugin unless you call detach
     * @param $plugin
     * @return $this
     */
    public function attach($plugin)
    {
        $this->plugin = $plugin;

        if(!$this->session_id || $this->handle_id) return $this;

        $this->janusAPI([
            'janus' => 'attach',
            'plugin' => $plugin,
            'transaction' => Str::random(12),
            'apisecret' => $this->api_secret
        ]);

        if(isset($this->api_response['data']['id'])) $this->handle_id = $this->api_response['data']['id'];

        else $this->handle_id = null;

        return $this;
    }

    /**
     * Detach from the current plugin/handle
     * @return $this
     */
    public function detach()
    {
        if(!$this->handle_id) return $this;

        $this->janusAPI([
            'janus' => 'detach',
            'transaction' => Str::random(12),
            'apisecret' => $this->api_secret
        ]);

        $this->handle_id = null;

        return $this;
    }

    /**
     * Disconnect from janus, destroying our session and handle/plugin
     * @return $this
     */
    public function disconnect()
    {
        $this->handle_id = null;

        if(!$this->session_id) return $this;

        $this->janusAPI([
            'janus' => 'destroy',
            'transaction' => Str::random(12),
            'apisecret' => $this->api_secret
        ]);

        $this->session_id = null;

        return $this;
    }

    /**
     * Send janus our message to the plugin
     * @param $message
     * @param null $jsep
     * @return $this
     */
    public function sendMessage($message, $jsep = null)
    {
        $this->plugin_payload = [
            'janus' => 'message',
            'body' => $message,
            'transaction' => Str::random(12),
            'apisecret' => $this->api_secret
        ];

        if($jsep) array_push($this->plugin_payload, ['jsep' => $jsep]);

        if(!$this->session_id || !$this->handle_id || !$this->plugin)
        {
            $this->plugin_response = [];
            return $this;
        }

        $this->janusAPI($this->plugin_payload)->setPluginResponse();

        return $this;
    }

    /**
     * Send janus our trickle
     * @param $candidate
     * @return $this
     */
    public function sendTrickleCandidate($candidate)
    {
        if(!$this->session_id || !$this->handle_id)
        {
            $this->plugin_response = [];
            $this->plugin_payload = [];
            return $this;
        }

        $this->plugin_payload = [
            'janus' => 'trickle',
            'candidate' => $candidate,
            'transaction' => Str::random(12),
            'apisecret' => $this->api_secret
        ];

        $this->janusAPI($this->plugin_payload)->setPluginResponse();

        return $this;
    }

    /**
     * Make POST/GET to janus, append session or handle ID if they exist
     * @param bool $post
     * @param array $data
     * @param null $route
     * @param bool $admin
     * @return $this
     */
    private function janusAPI($data = null, $route = null, $admin = false, $post = true)
    {
        if(!$this->janus_server) return $this;

        $client = Http::withOptions([
            'verify' => $this->self_signed
        ]);

        $server = $admin ? $this->janus_admin_server : $this->janus_server;
        $route = $route ? '/' . $route : '';
        $session = $this->session_id ? '/' . $this->session_id : '';
        $handle = $this->handle_id ? '/' . $this->handle_id : '';
        $uri = $server . $route . $session . $handle;

        if($this->debug) dump($data);

        try{
            $this->trackServerLatency();

            $response = $post ? $client->post($uri, [
                'json' => $data
            ]) : $client->get($uri);

            $this->reportServerLatency();

            if($this->debug) dump($response->headers());

            $this->api_response = json_decode($response->body(),true);

        }catch (Exception $e){
            report($e);
            if($this->debug) dump($e);
            $this->api_response = [];
        }

        if($this->debug) dump($this->api_response);

        if (!isset($this->api_response['janus']) || $this->api_response['janus'] === 'error') $this->logApiError($data, $uri);

        return $this;
    }

    /**
     * Called after plugin message to extract plugin data response
     * @return array|mixed
     */
    private function setPluginResponse()
    {
        if(isset($this->api_response['plugindata']['plugin'])
            && $this->api_response['plugindata']['plugin'] === $this->plugin
            && isset($this->api_response['plugindata']['data']))
        {
            $this->plugin_response = $this->api_response['plugindata']['data'];
        }
        else $this->plugin_response = [];

        return $this->plugin_response;
    }

    /**
     * Start micro timer for API interaction
     */
    private function trackServerLatency()
    {
        $this->ping_pong = microtime(true);
    }

    /**
     * Finish and calculate milliseconds for API call
     */
    private function reportServerLatency()
    {
        if($this->ping_pong)
        {
            $this->last_latency = round((microtime(true) - $this->ping_pong) * 1000);
            $this->ping_pong = null;
        }
    }

}