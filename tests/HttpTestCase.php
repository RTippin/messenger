<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Router;
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
    private ?string $statusOverride = null;

    /**
     * @param Application $app
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
        $this->statusOverride = null;

        parent::tearDown();
    }

    /**
     * Call this method inside any http tests to instruct us to log the response.
     *
     * @param string|null $status
     */
    public function logCurrentRequest(?string $status = null): void
    {
        if ($this->withApiLogging) {
            $this->shouldLogCurrentRequest = true;
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
            $currentRoute = app(Router::class)->getCurrentRoute();
            $status = $this->statusOverride
                ? $response->getStatusCode().'_'.$this->statusOverride
                : $response->getStatusCode();

            $this->storeResponse(
                $currentRoute->getName(),
                $currentRoute->uri(),
                $response->getContent(),
                implode('|', $currentRoute->methods()),
                $method,
                $status,
                $data,
                $response->getStatusCode(),
                $uri
            );
        }

        return $response;
    }

    /**
     * @param string $routeName
     * @param string $uri
     * @param string $response
     * @param string $methods
     * @param string $verb
     * @param string $status
     * @param array $payload
     * @param int $originalStatus
     * @param string $fullQuery
     */
    private function storeResponse(string $routeName,
                                   string $uri,
                                   string $response,
                                   string $methods,
                                   string $verb,
                                   string $status,
                                   array $payload,
                                   int $originalStatus,
                                   string $fullQuery): void
    {
        $file = __DIR__.'/../docs/generated/responses.json';
        $responses = json_decode(file_get_contents($file), true);
        $responses[$routeName]['uri'] = $uri;
        $responses[$routeName]['query'] = $fullQuery;
        $responses[$routeName]['methods'] = $methods;

        if ($originalStatus === 422) {
            if (count($payload)) {
                $payload = $this->sanitizePayload($payload);
            }

            $responses[$routeName][$verb][$status][] = [
                'payload' => $payload,
                'response' => json_decode($response, true),
            ];
        } else {
            if (count($payload)) {
                $responses[$routeName][$verb][$status]['payload'] = $this->sanitizePayload($payload);
            } elseif (in_array($verb, ['POST', 'PUT']) && ! count($payload)) {
                $responses[$routeName][$verb][$status]['payload'] = ['No Payload'];
            }

            $responses[$routeName][$verb][$status]['response'] = json_decode($response, true);
        }

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
                $payload[$key] = '(binary) - '.$item->getClientMimeType().' - '.$this->formatBytes($item->getSize());
            }

            if (is_string($item) && mb_strlen($item) > 1000) {
                $payload[$key] = '(string) '.mb_strlen($item).' characters.';
            }
        }

        return $payload;
    }

    /**
     * @param int $size
     * @return int|string
     */
    private static function formatBytes(int $size)
    {
        if ($size > 0) {
            $base = log($size) / log(1024);
            $suffixes = [' bytes', ' KB', ' MB', ' GB', ' TB'];

            return round(pow(1024, $base - floor($base)), 2).$suffixes[floor($base)];
        }

        return $size;
    }
}
