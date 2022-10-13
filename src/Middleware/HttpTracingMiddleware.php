<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Middleware;

use Illuminate\Support\Facades\Config;
use OpenTelemetry\SDK\Trace\Span;
use Symfony\Component\HttpFoundation\Response;
use Umbrellio\Jaravel\Services\Caller;
use Umbrellio\Jaravel\Services\Http\TracingRequestGuard;
use Umbrellio\Jaravel\Services\Span\ActiveSpanTraceIdRetriever;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Services\Span\SpanAttributeHelper;
use Umbrellio\Jaravel\Services\TraceIdHeaderRetriever;

class HttpTracingMiddleware
{
    private SpanCreator $spanCreator;
    private TracingRequestGuard $requestGuard;
    private ActiveSpanTraceIdRetriever $activeTraceIdRetriever;
    private TraceIdHeaderRetriever $traceIdHeaderRetriever;

    public function __construct(
        SpanCreator $spanCreator,
        TracingRequestGuard $requestGuard,
        ActiveSpanTraceIdRetriever $activeTraceIdRetriever,
        TraceIdHeaderRetriever $traceIdHeaderRetriever
    ) {
        $this->spanCreator = $spanCreator;
        $this->requestGuard = $requestGuard;
        $this->activeTraceIdRetriever = $activeTraceIdRetriever;
        $this->traceIdHeaderRetriever = $traceIdHeaderRetriever;
    }

    public function handle($request, callable $next)
    {
        if (!$this->requestGuard->allowRequest($request)) {
            return $next($request);
        }

        $traceIdHeader = $this->traceIdHeaderRetriever->retrieve(iterator_to_array($request->headers));

        $this->spanCreator->create(
            Caller::call(Config::get('jaravel.http.span_name'), [$request]),
            $traceIdHeader
        );

        $response = $next($request);

        $this->addTraceIdToHeaderIfNeeded($response);

        return $response;
    }

    public function terminate($request, $response)
    {
        $span = Span::getCurrent();
        $scope = $span->activate();

        $callableConfig = Config::get('jaravel.http.attributes', fn () => [
            'type' => 'http',
        ]);

        SpanAttributeHelper::setAttributes($span, Caller::call($callableConfig, [$request, $response]));

        $span->end();
        $scope->detach();
    }

    private function addTraceIdToHeaderIfNeeded(Response $response): void
    {
        $headerName = Config::get('jaravel.trace_id_header', null);

        if (!$headerName) {
            return;
        }

        $traceId = $this->activeTraceIdRetriever->retrieve();

        $response->headers->set($headerName, $traceId);
    }
}
