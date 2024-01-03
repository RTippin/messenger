<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Throwable;

class Handler implements ExceptionHandlerContract
{
    use ForwardsCalls;

    /**
     * @var ExceptionHandlerContract
     */
    private ExceptionHandlerContract $handler;

    /**
     * @param  ExceptionHandlerContract  $handler
     */
    public function __construct(ExceptionHandlerContract $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @inheritDoc
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof ModelNotFoundException
            && $request->routeIs('api.messenger*')) {
            return new JsonResponse([
                'message' => "Unable to locate the {$this->transformModelNotFound($e)} you requested.",
            ], 404);
        }

        return $this->handler->render($request, $e);
    }

    /**
     * @inheritDoc
     */
    public function report(Throwable $e): void
    {
        $this->handler->report($e);
    }

    /**
     * @inheritDoc
     */
    public function shouldReport(Throwable $e): bool
    {
        return $this->handler->shouldReport($e);
    }

    /**
     * @inheritDoc
     */
    public function renderForConsole($output, Throwable $e): void
    {
        $this->handler->renderForConsole($output, $e);
    }

    /**
     * @param  $method
     * @param  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->handler, $method, $parameters);
    }

    /**
     * @param  ModelNotFoundException  $exception
     * @return string
     */
    private function transformModelNotFound(ModelNotFoundException $exception): string
    {
        if (! is_null($exception->getModel())) {
            return Str::lower(
                ltrim(
                    preg_replace('/[A-Z]/', ' $0', class_basename($exception->getModel()))
                )
            );
        }

        return 'resource';
    }
}
