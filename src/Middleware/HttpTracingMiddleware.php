<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Middleware;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use OpenTracing\Reference;
use OpenTracing\Tracer;
use Symfony\Component\HttpFoundation\Response;
use Umbrellio\Jaravel\Services\Caller;
use Umbrellio\Jaravel\Services\Http\TracingRequestGuard;
use Umbrellio\Jaravel\Services\Span\ActiveSpanTraceIdRetriever;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Services\Span\SpanTagHelper;

class HttpTracingMiddleware
{
    private Tracer $tracer;
    private SpanCreator $spanCreator;
    private TracingRequestGuard $requestGuard;
    private ActiveSpanTraceIdRetriever $activeTraceIdRetriever;

    public function __construct(
        Tracer $tracer,
        SpanCreator $spanCreator,
        TracingRequestGuard $requestGuard,
        ActiveSpanTraceIdRetriever $activeTraceIdRetriever
    ) {
        $this->tracer = $tracer;
        $this->spanCreator = $spanCreator;
        $this->requestGuard = $requestGuard;
        $this->activeTraceIdRetriever = $activeTraceIdRetriever;
    }

    public function handle($request, callable $next)
    {
        if (!$this->requestGuard->allowRequest($request)) {
            return $next($request);
        }

        Log::channel('jaravel')->info('http: ' . json_encode(iterator_to_array($request->headers)));
        Log::channel('jaravel')->info('http_full: ' . json_encode($request));

        $this->spanCreator->create(
            Caller::call(Config::get('jaravel.http.span_name'), [$request]),
            iterator_to_array($request->headers),
            Reference::CHILD_OF
        );

        $response = $next($request);

        $this->addTraceIdToHeaderIfNeeded($response);

        return $response;
    }

    public function terminate($request, $response)
    {
        $scope = $this->tracer->getScopeManager()
            ->getActive();
        if (!$scope) {
            $this->tracer->flush();
            return;
        }

        $callableConfig = Config::get('jaravel.http.tags', fn () => [
            'type' => 'http',
        ]);

        SpanTagHelper::setTags($scope->getSpan(), Caller::call($callableConfig, [$request, $response]));

        $scope->close();
        $this->tracer->flush();
    }

    private function addTraceIdToHeaderIfNeeded(Response $response): void
    {
        $headerName = Config::get('jaravel.trace_id_header', null);
        if (!$headerName) {
            return;
        }

        $traceId = $this->activeTraceIdRetriever->retrieve();
        if (!$traceId) {
            return;
        }

        $response->headers->set($headerName, $traceId);
    }
}
