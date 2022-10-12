<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Middleware;

use Illuminate\Support\Facades\Config;
use Umbrellio\Jaravel\Services\Caller;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Services\Span\SpanAttributeHelper;
use Umbrellio\Jaravel\Services\TraceIdHeaderRetriever;

class JobTracingMiddleware
{
    public const JOB_TRACING_CONTEXT_FIELD = 'tracingContext';

    private SpanCreator $spanCreator;
    private TraceIdHeaderRetriever $traceIdHeaderRetriever;

    public function __construct(SpanCreator $spanCreator, TraceIdHeaderRetriever $traceIdHeaderRetriever)
    {
        $this->spanCreator = $spanCreator;
        $this->traceIdHeaderRetriever = $traceIdHeaderRetriever;
    }

    public function handle($job, callable $next)
    {
        $payload = $job->{self::JOB_TRACING_CONTEXT_FIELD} ?? [];

        $traceIdHeader = $this->traceIdHeaderRetriever->retrieve($payload);

        $span = $this->spanCreator->create(
            Caller::call(Config::get('jaravel.job.span_name'), [$job, $job->job ?? null]),
            $traceIdHeader
        );
        $spanScope = $span->activate();

        $next($job);

        $callableConfig = Config::get('jaravel.job.tags', fn () => [
            'type' => 'job',
        ]);

        SpanAttributeHelper::setAttributes($span, Caller::call($callableConfig, [$job, $job->job ?? null]));

        $span->end();
        $spanScope->detach();
    }
}
