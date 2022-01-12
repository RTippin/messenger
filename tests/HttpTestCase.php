<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Testing\TestResponse;

class HttpTestCase extends FeatureTestCase
{
    /**
     * Location of our responses file for storing test case responses.
     */
    const ResponseFile = __DIR__.'/../docs/generated/responses.json';

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
     * @param  string|null  $status
     */
    public function logCurrentRequest(?string $status = null): void
    {
        if ($this->withHttpLogging) {
            $this->shouldLogCurrentRequest = true;
            $this->statusOverride = $status;
        }
    }

    /**
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return TestResponse
     */
    public function json($method, $uri, array $data = [], array $headers = []): TestResponse
    {
        $response = parent::json($method, $uri, $data, $headers);

        if ($this->shouldLogCurrentRequest) {
            $this->storeTestResponse(
                app(Router::class)->getCurrentRoute(),
                $response,
                $method,
                $uri,
                $data
            );
        }

        return $response;
    }

    /**
     * @param  Route  $route
     * @param  TestResponse  $response
     * @param  string  $method
     * @param  string  $query
     * @param  array  $payload
     */
    private function storeTestResponse(Route $route,
                                       TestResponse $response,
                                       string $method,
                                       string $query,
                                       array $payload): void
    {
        $responses = $this->getResponsesFile();
        $status = $this->generateResponseStatus($response);

        $responses[$route->getName()]['uri'] = $route->uri();
        $responses[$route->getName()]['query'] = $query;
        $responses[$route->getName()]['methods'] = implode('|', $route->methods());

        if ($response->getStatusCode() === 422) {
            $responses[$route->getName()][$method][$status][] = [
                'payload' => count($payload) ? $this->sanitizePayload($payload) : $payload,
                'response' => json_decode($response->getContent(), true),
            ];

            $this->storeResponsesFile($responses);

            return;
        }

        if (count($payload)) {
            $responses[$route->getName()][$method][$status]['payload'] = $this->sanitizePayload($payload);
        } elseif (in_array($method, ['POST', 'PUT'])) {
            $responses[$route->getName()][$method][$status]['payload'] = ['No Payload'];
        }

        if ($response->headers->get('content-type') !== 'application/json' && $method !== 'DELETE') {
            $responses[$route->getName()][$method][$status]['response'] = [
                'context' => 'Asset Response',
                'headers' => [
                    'content-type' => $response->headers->get('content-type'),
                    'content-length' => $response->headers->get('content-length'),
                ],
            ];
        } else {
            $responses[$route->getName()][$method][$status]['response'] = json_decode($response->getContent(), true);
        }

        $this->storeResponsesFile($responses);
    }

    /**
     * @return array
     */
    private function getResponsesFile(): array
    {
        if (! file_exists(self::ResponseFile)) {
            return [];
        }

        return json_decode(file_get_contents(self::ResponseFile), true) ?: [];
    }

    /**
     * @param  array  $responses
     */
    private function storeResponsesFile(array $responses): void
    {
        file_put_contents(self::ResponseFile, json_encode($responses));
    }

    /**
     * @param  TestResponse  $response
     * @return string
     */
    private function generateResponseStatus(TestResponse $response): string
    {
        return $this->statusOverride
            ? $response->getStatusCode().'_'.$this->statusOverride
            : $response->getStatusCode();
    }

    /**
     * @param  array  $payload
     * @return array
     */
    private function sanitizePayload(array $payload): array
    {
        foreach ($payload as $key => $item) {
            if ($item instanceof UploadedFile) {
                $payload[$key] = '(binary) - '.$item->getClientMimeType().' - '.$this->formatBytes($item->getSize());
            }

            if (is_string($item) && mb_strlen($item) > 50) {
                $payload[$key] = '(string) '.mb_strlen($item).' characters.';
            }
        }

        return $payload;
    }

    /**
     * @param  int  $size
     * @return int|string
     */
    private function formatBytes(int $size)
    {
        if ($size > 0) {
            $base = log($size) / log(1024);
            $suffixes = [' bytes', ' KB', ' MB', ' GB', ' TB'];

            return round(pow(1024, $base - floor($base)), 2).$suffixes[floor($base)];
        }

        return $size;
    }
}
