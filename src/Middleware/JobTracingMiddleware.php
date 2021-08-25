<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Middleware;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use OpenTracing\Reference;
use OpenTracing\Tracer;
use Umbrellio\Jaravel\Services\Caller;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Services\Span\SpanTagHelper;

class JobTracingMiddleware
{
    public const JOB_TRACING_CONTEXT_FIELD = 'tracingContext';

    public function handle($job, callable $next)
    {
        /** @var Tracer $tracer */
        $tracer = App::make(Tracer::class);
        /** @var SpanCreator $spanCreator */
        $spanCreator = App::make(SpanCreator::class);

        $tracingContextField = self::JOB_TRACING_CONTEXT_FIELD;
        $payload = $job->{$tracingContextField} ?? [];

        Log::channel('jaravel')->info('http: ' . json_encode($payload));

        $span = $spanCreator->create(
            Caller::call(Config::get('jaravel.job.span_name'), [$job, $job->job ?? null]),
            $payload,
            Reference::FOLLOWS_FROM
        );

        $next($job);

        $callableConfig = Config::get('jaravel.job.tags', fn () => [
            'type' => 'job',
        ]);

        SpanTagHelper::setTags($span, Caller::call($callableConfig, [$job, $job->job ?? null]));

        optional($tracer->getScopeManager()->getActive())
            ->close();
        $tracer->flush();
    }
}
