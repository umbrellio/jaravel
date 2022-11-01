<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\SDK\Trace\Span;
use Symfony\Component\HttpFoundation\Response;
use Umbrellio\Jaravel\Services\Caller;
use Umbrellio\Jaravel\Services\Http\TracingRequestGuard;
use Umbrellio\Jaravel\Services\Span\ActiveSpanTraceIdRetriever;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Services\Span\SpanTagHelper;
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

    /** @param Request $request */
    public function handle($request, callable $next)
    {
        if (!$this->requestGuard->allowRequest($request)) {
            return $next($request);
        }

        $headers = iterator_to_array($request->headers);
        $traceIdHeader = $this->traceIdHeaderRetriever->retrieve($headers);
        $traceStateHeader = $this->traceIdHeaderRetriever->retrieve($headers, TraceContextPropagator::TRACESTATE);

        $this->spanCreator->create(
            Caller::call(Config::get('jaravel.http.span_name'), [$request]),
            $traceIdHeader,
            $traceStateHeader
        )->activate();

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }

    public function terminate($request, $response)
    {
        $span = Span::getCurrent();
        $scope = $span->activate();

        $callableConfig = Config::get('jaravel.http.tags', fn () => [
            'type' => 'http',
        ]);

        SpanTagHelper::setTags($span, Caller::call($callableConfig, [$request, $response]));

        $span->end();
        $scope->detach();
    }
}
