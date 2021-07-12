<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\TestResponse;

class HttpTestCase extends FeatureTestCase
{
    /**
     * Set TRUE to run all http test with
     * logging responses to file enabled.
     */
    protected bool $withApiLogging = false;

    /**
     * Logs the current request/payload to the json file.
     *
     * @var bool
     */
    private bool $shouldLogCurrentRequest = false;

    /**
     * @var string|null
     */
    private ?string $currentRouteName = null;

    /**
     * @var string|null
     */
    private ?string $statusOverride = null;

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        if (env('LOG_API') === true) {
            $this->withApiLogging = true;
        }
    }

    /**
     * No need for throttle middleware.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /**
     * Reset the logging requirements.
     */
    protected function tearDown(): void
    {
        $this->shouldLogCurrentRequest = false;
        $this->currentRouteName = null;
        $this->statusOverride = null;

        parent::tearDown();
    }

    /**
     * Call this method inside any http tests to instruct us to log the response.
     *
     * @param string $routeName
     * @param string|null $status
     */
    public function logCurrentRequest(string $routeName, ?string $status = null): void
    {
        if ($this->withApiLogging) {
            $this->shouldLogCurrentRequest = true;
            $this->currentRouteName = $routeName;
            $this->statusOverride = $status;
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return TestResponse
     */
    public function json($method, $uri, array $data = [], array $headers = []): TestResponse
    {
        $response = parent::json($method, $uri, $data, $headers);

        if ($this->shouldLogCurrentRequest) {
            $status = $this->statusOverride
                ? $response->getStatusCode().'_'.$this->statusOverride
                : $response->getStatusCode();

            $this->storeResponse(
                $this->currentRouteName,
                $uri,
                $response->getContent(),
                $method,
                $status,
                $data
            );
        }

        return $response;
    }

    /**
     * @param string $routeName
     * @param string $uri
     * @param string $response
     * @param string $verb
     * @param string $status
     * @param array $payload
     */
    private function storeResponse(string $routeName,
                                     string $uri,
                                     string $response,
                                     string $verb,
                                     string $status,
                                     array $payload): void
    {
        $file = __DIR__.'/../docs/generated/responses.json';
        $responses = json_decode(file_get_contents($file), true);
        $responses[$routeName][$verb][$status]['uri'] = $uri;

        if (count($payload)) {
            $responses[$routeName][$verb][$status]['payload'] = $this->sanitizePayload($payload);
        }

        $responses[$routeName][$verb][$status]['response'] = json_decode($response, true);
        file_put_contents($file, json_encode($responses));
    }

    /**
     * @param array $payload
     * @return array
     */
    private function sanitizePayload(array $payload): array
    {
        foreach ($payload as $key => $item) {
            if ($item instanceof UploadedFile) {
                $payload[$key] = '(binary)';
            }
        }

        return $payload;
    }
}
