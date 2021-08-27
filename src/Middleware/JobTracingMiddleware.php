<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Middleware;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use OpenTracing\Reference;
use OpenTracing\Tracer;
use Umbrellio\Jaravel\Services\Caller;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Services\Span\SpanTagHelper;
use Umbrellio\Jaravel\Services\TraceIdHeaderRetriever;

class JobTracingMiddleware
{
    public const JOB_TRACING_CONTEXT_FIELD = 'tracingContext';

    private Tracer $tracer;
    private SpanCreator $spanCreator;
    private TraceIdHeaderRetriever $traceIdHeaderRetriever;

    public function __construct(Tracer $tracer, SpanCreator $spanCreator, TraceIdHeaderRetriever $traceIdHeaderRetriever)
    {
        $this->tracer = $tracer;
        $this->spanCreator = $spanCreator;
        $this->traceIdHeaderRetriever = $traceIdHeaderRetriever;
    }

    public function handle($job, callable $next)
    {
        $payload = $job->{self::JOB_TRACING_CONTEXT_FIELD} ?? [];

        $traceIdHeader = $this->traceIdHeaderRetriever->retrieve($payload);

        $span = $this->spanCreator->create(
            Caller::call(Config::get('jaravel.job.span_name'), [$job, $job->job ?? null]),
            $traceIdHeader,
            Reference::FOLLOWS_FROM
        );

        $next($job);

        $callableConfig = Config::get('jaravel.job.tags', fn () => [
            'type' => 'job',
        ]);

        SpanTagHelper::setTags($span, Caller::call($callableConfig, [$job, $job->job ?? null]));

        optional($this->tracer->getScopeManager()->getActive())
            ->close();
        $this->tracer->flush();
    }
}
