<?php
namespace Bellissimopizza\BellissimoLog\HttpLog;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;

class GuzzleLogMiddleware
{
    protected float|int $startDateTime;
    protected float|int|null $executionTime = null;
    protected ?TransferStats $logStats = null;

    /**
     * Middleware that logs requests, responses, and errors using a message formatter.
     */
    public function __construct()
    {
        $this->startDateTime = microtime(true);
    }

    /**
     * @param callable $handler
     * @return callable
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            if (isset($options['on_stats'])) {
                $options['on_stats'] = function (TransferStats $stats) {
                    $this->logStats = $stats;
                };
            }

            return $handler($request, $options)
                ->then(
                    $this->handleSuccess($request, $options),
                    $this->handleFailure($request, $options)
                );
        };
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return callable
     */
    private function handleSuccess(
        RequestInterface $request,
        array            $options = []
    ): callable
    {
        $this->executionTime =  microtime(true) - $this->startDateTime;
        return function (ResponseInterface $response) use ($request, $options) {
            $this->writeRequestLog($request, $response);

            return $response;
        };
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return callable
     */
    private function handleFailure(
        RequestInterface $request,
        array            $options = []
    ): callable
    {
        $this->executionTime =  microtime(true) - $this->startDateTime;
        return function (Exception $reason) use ($request, $options) {
            $response = $reason instanceof RequestExceptionInterface ? $reason->getResponse() : null;
            $this->writeRequestLog($request, $response, $reason);

            return Create::rejectionFor($reason);
        };
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param Exception|null $exception
     * @return void
     */
    protected function writeRequestLog(
        RequestInterface  $request,
        ResponseInterface $response,
        Exception         $exception = null): void
    {
        $requestId = request()->attributes->get('X-Request-ID');

        $responseContent = (json_last_error() != JSON_ERROR_NONE) ? $response->getBody()->getContents() : json_decode($response->getBody()->getContents(), true);

        Log::debug('Request', [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'url' => $request->getUri(),
//            'headers' => request()->headers->all(),
            'payload' => $responseContent,
            'timestamp' => now()->toIso8601String()
        ]);

        if ($exception) {
            Log::debug('Exception', [
                'request_id' => $requestId,
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'execution_time' => round($this->executionTime, 4) . 's',
                'timestamp' => now()->toIso8601String()
            ]);
        } else {
            Log::debug('Response', [
                'request_id' => $requestId,
                'status' => $response->getStatusCode(),
                'execution_time' => round($this->executionTime, 4) . 's',
                'timestamp' => now()->toIso8601String(),
                'response' => $responseContent
            ]);
        }

    }
}