<?php

namespace RTippin\Messenger\Services\Janus;

use Exception;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Log\Logger;
use Illuminate\Support\Str;

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
     * @var ConfigRepository
     */
    private ConfigRepository $configRepo;

    /**
     * @var HttpClient
     */
    private HttpClient $httpClient;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * JanusServer constructor.
     * @param ConfigRepository $configRepo
     * @param HttpClient $httpClient
     * @param Logger $logger
     */
    public function __construct(ConfigRepository $configRepo,
                                HttpClient $httpClient,
                                Logger $logger)
    {
        $this->configRepo = $configRepo;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->boot();
    }

    /**
     * Setup our config.
     */
    private function boot(): void
    {
        $this->apiSecret = $this->configRepo->get('janus.api_secret');
        $this->logErrors = $this->configRepo->get('janus.log_failures');
        $this->janusServer = $this->configRepo->get('janus.server_endpoint');
        $this->janusAdminServer = $this->configRepo->get('janus.server_admin_endpoint');
        $this->debug = $this->configRepo->get('janus.backend_debug');
        $this->selfSigned = $this->configRepo->get('janus.backend_ssl');
    }

    /**
     * Log an API error if logging enabled.
     * @param null $data
     * @param null $route
     * @return void
     */
    private function logApiError($data = null, $route = null): void
    {
        if ($this->logErrors) {
            $this->logger->warning('janus.api', [
                'payload' => $data,
                'route' => $route,
                'response' => $this->apiResponse,
            ]);
        }
    }

    /**
     * Log error from the loaded plugin method if logging enabled.
     * @param string $action
     * @param array $extra
     * @return void
     */
    public function logPluginError(string $action = '', array $extra = []): void
    {
        if ($this->logErrors) {
            $this->logger->warning($this->plugin.' - '.$action, [
                'payload' => $this->pluginPayload,
                'response' => $this->apiResponse,
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

        return $this->apiResponse;
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
        $this->apiSecret = $this->configRepo->get('janus.api_secret');
        $this->logErrors = $this->configRepo->get('janus.log_failures');
        $this->janusServer = $this->configRepo->get('janus.server_endpoint');
        $this->janusAdminServer = $this->configRepo->get('janus.server_admin_endpoint');
        $this->debug = $this->configRepo->get('janus.backend_debug');
        $this->selfSigned = $this->configRepo->get('janus.backend_ssl');

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
     * Return the response from a plugin.
     * @return array|string
     */
    public function getPluginResponse()
    {
        return $this->pluginResponse;
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
            'apisecret' => $this->apiSecret,
        ]);

        $this->sessionId = isset($this->apiResponse['data']['id'])
            ? $this->apiResponse['data']['id']
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

        $this->janusAPI($this->pluginPayload)
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

        $this->janusAPI($this->pluginPayload)
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
        if (! $this->janusServer) {
            return $this;
        }

        $client = $this->httpClient->withOptions([
            'verify' => $this->selfSigned,
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

            $this->apiResponse = $response->json();
        } catch (Exception $e) {
            report($e);
            if ($this->debug) {
                dump($e);
            }
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
     * @return array
     */
    private function setPluginResponse(): array
    {
        if (isset($this->apiResponse['plugindata']['plugin'])
            && $this->apiResponse['plugindata']['plugin'] === $this->plugin
            && isset($this->apiResponse['plugindata']['data'])) {
            $this->pluginResponse = $this->apiResponse['plugindata']['data'];
        } else {
            $this->pluginResponse = [];
        }

        return $this->pluginResponse;
    }

    /**
     * Start micro timer for API interaction.
     * @return void
     */
    private function trackServerLatency(): void
    {
        $this->pingPong = microtime(true);
    }

    /**
     * Finish and calculate milliseconds for API call.
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
