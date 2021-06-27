<?php

namespace RTippin\Messenger\Services\Janus;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Janus Media Server REST interface
 * https://janus.conf.meetecho.com/docs/rest.html.
 */
class JanusServer
{
    /**
     * @var string
     */
    private string $janusServer;

    /**
     * @var string
     */
    private string $janusAdminServer;

    /**
     * @var string
     */
    private string $apiSecret;

    /**
     * @var bool
     */
    private bool $logErrors;

    /**
     * @var bool
     */
    private bool $selfSigned;

    /**
     * @var bool
     */
    private bool $debug;

    /**
     * @var null|int|float
     */
    private $pingPong = null;

    /**
     * @var null|int|float
     */
    private $lastLatency = null;

    /**
     * @var null|string
     */
    private ?string $sessionId = null;

    /**
     * @var null|string
     */
    private ?string $handleId = null;

    /**
     * @var null|string
     */
    private ?string $plugin = null;

    /**
     * @var array
     */
    private array $apiResponse = [];

    /**
     * @var array
     */
    private array $pluginPayload = [];

    /**
     * @var array
     */
    private array $pluginResponse = [];

    /**
     * JanusServer constructor.
     */
    public function __construct()
    {
        $this->apiSecret = config('janus.api_secret');
        $this->logErrors = config('janus.log_failures');
        $this->janusServer = config('janus.server_endpoint');
        $this->janusAdminServer = config('janus.server_admin_endpoint');
        $this->debug = config('janus.backend_debug');
        $this->selfSigned = config('janus.backend_ssl');
    }

    /**
     * Log an API error if logging enabled.
     *
     * @param null $data
     * @param null $route
     * @return void
     */
    private function logApiError($data = null, $route = null): void
    {
        if ($this->logErrors) {
            Log::warning('janus.api', [
                'payload' => $data,
                'route' => $route,
                'response' => $this->apiResponse,
            ]);
        }
    }

    /**
     * Log error from the loaded plugin method if logging enabled.
     *
     * @param string $action
     * @param array $extra
     * @return void
     */
    public function logPluginError(string $action = '', array $extra = []): void
    {
        if ($this->logErrors) {
            Log::warning($this->plugin.' - '.$action, [
                'payload' => $this->pluginPayload,
                'response' => $this->apiResponse,
                'extra' => $extra,
            ]);
        }
    }

    /**
     * Retrieve the janus server instance details.
     *
     * @return array
     */
    public function serverInfo(): array
    {
        $this->janusAPI(null, 'info', false, false);

        return $this->apiResponse;
    }

    /**
     * Ping janus to see if it is alive.
     *
     * @return array
     */
    public function serverPing(): array
    {
        $this->janusAPI([
            'janus' => 'ping',
            'transaction' => Str::random(12),
        ], null, true);

        if (isset($this->apiResponse['janus'])
            && $this->apiResponse['janus'] === 'pong') {
            return [
                'pong' => true,
                'latency' => $this->lastLatency,
                'message' => $this->lastLatency.' milliseconds',
            ];
        }

        return [
            'pong' => false,
        ];
    }

    /**
     * Set the Janus configs defaulted in the constructor and class properties
     * Use property name as key => value.
     *
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
     * Easily set debug at any part of a chain.
     *
     * @param bool $debug
     * @return $this
     */
    public function debug(bool $debug = true): self
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Return the response from a plugin.
     *
     * @return array
     */
    public function getPluginResponse(): array
    {
        return $this->pluginResponse;
    }

    /**
     * Connect with janus to set the session ID for this cycle.
     *
     * @return $this
     */
    public function connect(): self
    {
        $this->janusAPI([
            'janus' => 'create',
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ]);

        $this->sessionId = $this->apiResponse['data']['id'] ?? null;

        return $this;
    }

    /**
     * Attach to the janus plugin to get a handle ID. All request
     * in this cycle will go to this plugin unless you call detach.
     *
     * @param string $plugin
     * @return $this
     */
    public function attach(string $plugin): self
    {
        $this->plugin = $plugin;

        if (! $this->sessionId || $this->handleId) {
            return $this;
        }

        $this->janusAPI([
            'janus' => 'attach',
            'plugin' => $plugin,
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ]);

        if (isset($this->apiResponse['data']['id'])) {
            $this->handleId = $this->apiResponse['data']['id'];
        } else {
            $this->handleId = null;
        }

        return $this;
    }

    /**
     * Detach from the current plugin/handle.
     *
     * @return $this
     */
    public function detach(): self
    {
        if (! $this->handleId) {
            return $this;
        }

        $this->janusAPI([
            'janus' => 'detach',
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ]);

        $this->handleId = null;

        return $this;
    }

    /**
     * Disconnect from janus, destroying our session and handle/plugin.
     *
     * @return $this
     */
    public function disconnect(): self
    {
        $this->handleId = null;

        if (! $this->sessionId) {
            return $this;
        }

        $this->janusAPI([
            'janus' => 'destroy',
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ]);

        $this->sessionId = null;

        return $this;
    }

    /**
     * Send janus our message to the plugin.
     *
     * @param array $message
     * @param string|null $jsep
     * @return $this
     */
    public function sendMessage(array $message, string $jsep = null): self
    {
        $this->pluginPayload = [
            'janus' => 'message',
            'body' => $message,
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ];

        if ($jsep) {
            array_push($this->pluginPayload, ['jsep' => $jsep]);
        }

        if (! $this->sessionId
            || ! $this->handleId
            || ! $this->plugin) {
            $this->pluginResponse = [];

            return $this;
        }

        $this->janusAPI($this->pluginPayload)->setPluginResponse();

        return $this;
    }

    /**
     * Send janus our trickle.
     * @param string $candidate
     * @return $this
     */
    public function sendTrickleCandidate(string $candidate): self
    {
        if (! $this->sessionId || ! $this->handleId) {
            $this->pluginResponse = [];
            $this->pluginPayload = [];

            return $this;
        }

        $this->pluginPayload = [
            'janus' => 'trickle',
            'candidate' => $candidate,
            'transaction' => Str::random(12),
            'apisecret' => $this->apiSecret,
        ];

        $this->janusAPI($this->pluginPayload)->setPluginResponse();

        return $this;
    }

    /**
     * Make POST/GET to janus, append session or handle ID if they exist.
     *
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
        if (! $this->janusServer) {
            return $this;
        }

        $client = Http::withOptions([
            'verify' => $this->selfSigned,
            'timeout' => 30,
        ]);

        $server = $admin ? $this->janusAdminServer : $this->janusServer;
        $route = $route ? '/'.$route : '';
        $session = $this->sessionId ? '/'.$this->sessionId : '';
        $handle = $this->handleId ? '/'.$this->handleId : '';
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

            $this->apiResponse = $response->successful()
                ? $response->json()
                : [];
        } catch (Throwable $e) {
            report($e);
            $this->apiResponse = [];
        }

        if ($this->debug) {
            dump($this->apiResponse);
        }

        if (! isset($this->apiResponse['janus'])
            || $this->apiResponse['janus'] === 'error') {
            $this->logApiError($data, $uri);
        }

        return $this;
    }

    /**
     * Called after plugin message to extract plugin data response.
     *
     * @return void
     */
    private function setPluginResponse(): void
    {
        if (isset($this->apiResponse['plugindata']['plugin'])
            && $this->apiResponse['plugindata']['plugin'] === $this->plugin
            && isset($this->apiResponse['plugindata']['data'])) {
            $this->pluginResponse = $this->apiResponse['plugindata']['data'];
        } else {
            $this->pluginResponse = [];
        }
    }

    /**
     * Start micro timer for API interaction.
     *
     * @return void
     */
    private function trackServerLatency(): void
    {
        $this->pingPong = microtime(true);
    }

    /**
     * Finish and calculate milliseconds for API call.
     *
     * @return void
     */
    private function reportServerLatency(): void
    {
        if ($this->pingPong) {
            $this->lastLatency = round((microtime(true) - $this->pingPong) * 1000);
            $this->pingPong = null;
        }
    }
}
