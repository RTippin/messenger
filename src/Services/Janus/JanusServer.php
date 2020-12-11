<?php

namespace RTippin\Messenger\Services\Janus;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JanusServer
{
    /**
     * Janus Media Server REST interface
     * https://janus.conf.meetecho.com/docs/rest.html.
     */

    /**
     * @var string
     */
    private string $janus_server;

    /**
     * @var string
     */
    private string $janus_admin_server;

    /**
     * @var string
     */
    private string $api_secret;

    /**
     * @var bool
     */
    protected bool $log_errors;

    /**
     * @var bool
     */
    private bool $self_signed;

    /**
     * @var bool
     */
    protected bool $debug;

    /**
     * @var null|int|float
     */
    protected $ping_pong = null;

    /**
     * @var null|int|float
     */
    protected $last_latency = null;

    /**
     * @var null|string
     */
    private ?string $session_id = null;

    /**
     * @var null|string
     */
    private ?string $handle_id = null;

    /**
     * @var null|string
     */
    protected ?string $plugin = null;

    /**
     * @var array
     */
    protected array $api_response = [];

    /**
     * @var array
     */
    protected array $plugin_payload = [];

    /**
     * @var array
     */
    protected array $plugin_response = [];

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
     * Log an API error if logging enabled.
     * @param null $data
     * @param null $route
     * @return void
     */
    private function logApiError($data = null, $route = null): void
    {
        if ($this->log_errors) {
            Log::warning('janus.api', [
                'payload' => $data,
                'route' => $route,
                'response' => $this->api_response,
            ]);
        }
    }

    /**
     * Log error from the loaded plugin method if logging enabled.
     * @param string $action
     * @param array $extra
     * @return void
     */
    protected function logPluginError(string $action = '', array $extra = []): void
    {
        if ($this->log_errors) {
            Log::warning($this->plugin.' - '.$action, [
                'payload' => $this->plugin_payload,
                'response' => $this->api_response,
                'extra' => $extra,
            ]);
        }
    }

    /**
     * Retrieve the janus server instance details.
     * @return array|bool|mixed
     */
    public function serverInfo()
    {
        $this->janusAPI(null, 'info', false, false);

        return $this->api_response;
    }

    /**
     * Ping janus to see if it is alive.
     * @return array
     */
    public function serverPing(): array
    {
        $this->janusAPI([
            'janus' => 'ping',
            'transaction' => Str::random(12),
        ], null, true);

        if (isset($this->api_response['janus'])
            && $this->api_response['janus'] === 'pong') {
            return [
                'pong' => true,
                'latency' => $this->last_latency,
                'message' => $this->last_latency.' milliseconds',
            ];
        }

        return [
            'pong' => false,
        ];
    }

    /**
     * Set the Janus configs defaulted in the constructor and class properties
     * Use property name as key => value.
     * @param array|null $config
     * @return $this
     */
    public function setConfig(array $config = null): self
    {
        if ($config && count($config)) {
            foreach ($config as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Set the configs in the constructor back to defaults.
     * @return $this
     */
    public function resetConfig(): self
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
     * Easily set debug at any part of a chain.
     * @param bool $debug
     * @return $this
     */
    public function debug(bool $debug = true): self
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Connect with janus to set the session ID for this cycle.
     * @return $this
     */
    public function connect(): self
    {
        $this->janusAPI([
            'janus' => 'create',
            'transaction' => Str::random(12),
            'apisecret' => $this->api_secret,
        ]);

        $this->session_id = isset($this->api_response['data']['id'])
            ? $this->api_response['data']['id']
            : null;

        return $this;
    }

    /**
     * Attach to the janus plugin to get a handle ID. All request
     * in this cycle will go to this plugin unless you call detach.
     * @param string $plugin
     * @return $this
     */
    public function attach(string $plugin): self
    {
        $this->plugin = $plugin;

        if (! $this->session_id || $this->handle_id) {
            return $this;
        }

        $this->janusAPI([
            'janus' => 'attach',
            'plugin' => $plugin,
            'transaction' => Str::random(12),
            'apisecret' => $this->api_secret,
        ]);

        if (isset($this->api_response['data']['id'])) {
            $this->handle_id = $this->api_response['data']['id'];
        } else {
            $this->handle_id = null;
        }

        return $this;
    }

    /**
     * Detach from the current plugin/handle.
     * @return $this
     */
    public function detach(): self
    {
        if (! $this->handle_id) {
            return $this;
        }

        $this->janusAPI([
            'janus' => 'detach',
            'transaction' => Str::random(12),
            'apisecret' => $this->api_secret,
        ]);

        $this->handle_id = null;

        return $this;
    }

    /**
     * Disconnect from janus, destroying our session and handle/plugin.
     * @return $this
     */
    public function disconnect(): self
    {
        $this->handle_id = null;

        if (! $this->session_id) {
            return $this;
        }

        $this->janusAPI([
            'janus' => 'destroy',
            'transaction' => Str::random(12),
            'apisecret' => $this->api_secret,
        ]);

        $this->session_id = null;

        return $this;
    }

    /**
     * Send janus our message to the plugin.
     * @param array $message
     * @param string|null $jsep
     * @return $this
     */
    public function sendMessage(array $message, string $jsep = null): self
    {
        $this->plugin_payload = [
            'janus' => 'message',
            'body' => $message,
            'transaction' => Str::random(12),
            'apisecret' => $this->api_secret,
        ];

        if ($jsep) {
            array_push($this->plugin_payload, ['jsep' => $jsep]);
        }

        if (! $this->session_id
            || ! $this->handle_id
            || ! $this->plugin) {
            $this->plugin_response = [];

            return $this;
        }

        $this->janusAPI($this->plugin_payload)
            ->setPluginResponse();

        return $this;
    }

    /**
     * Send janus our trickle.
     * @param string $candidate
     * @return $this
     */
    public function sendTrickleCandidate(string $candidate): self
    {
        if (! $this->session_id || ! $this->handle_id) {
            $this->plugin_response = [];
            $this->plugin_payload = [];

            return $this;
        }

        $this->plugin_payload = [
            'janus' => 'trickle',
            'candidate' => $candidate,
            'transaction' => Str::random(12),
            'apisecret' => $this->api_secret,
        ];

        $this->janusAPI($this->plugin_payload)
            ->setPluginResponse();

        return $this;
    }

    /**
     * Make POST/GET to janus, append session or handle ID if they exist.
     * @param array $data
     * @param string|null $route
     * @param bool $admin
     * @param bool $post
     * @return $this
     */
    private function janusAPI(array $data = [],
                              string $route = null,
                              bool $admin = false,
                              bool $post = true): self
    {
        if (! $this->janus_server) {
            return $this;
        }

        $client = Http::withOptions([
            'verify' => $this->self_signed,
        ]);

        $server = $admin ? $this->janus_admin_server : $this->janus_server;
        $route = $route ? '/'.$route : '';
        $session = $this->session_id ? '/'.$this->session_id : '';
        $handle = $this->handle_id ? '/'.$this->handle_id : '';
        $uri = $server.$route.$session.$handle;

        if ($this->debug) {
            dump($data);
        }

        try {
            $this->trackServerLatency();

            $response = $post
                ? $client->post($uri, $data)
                : $client->get($uri);

            $this->reportServerLatency();

            if ($this->debug) {
                dump($response->headers());
            }

            $this->api_response = $response->json();
        } catch (Exception $e) {
            report($e);
            if ($this->debug) {
                dump($e);
            }
            $this->api_response = [];
        }

        if ($this->debug) {
            dump($this->api_response);
        }

        if (! isset($this->api_response['janus'])
            || $this->api_response['janus'] === 'error') {
            $this->logApiError($data, $uri);
        }

        return $this;
    }

    /**
     * Called after plugin message to extract plugin data response.
     * @return array
     */
    private function setPluginResponse(): array
    {
        if (isset($this->api_response['plugindata']['plugin'])
            && $this->api_response['plugindata']['plugin'] === $this->plugin
            && isset($this->api_response['plugindata']['data'])) {
            $this->plugin_response = $this->api_response['plugindata']['data'];
        } else {
            $this->plugin_response = [];
        }

        return $this->plugin_response;
    }

    /**
     * Start micro timer for API interaction.
     * @return void
     */
    private function trackServerLatency(): void
    {
        $this->ping_pong = microtime(true);
    }

    /**
     * Finish and calculate milliseconds for API call.
     * @return void
     */
    private function reportServerLatency(): void
    {
        if ($this->ping_pong) {
            $this->last_latency = round((microtime(true) - $this->ping_pong) * 1000);
            $this->ping_pong = null;
        }
    }
}
