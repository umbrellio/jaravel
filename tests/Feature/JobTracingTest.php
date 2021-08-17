<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use Illuminate\Support\Facades\Bus;
use Jaeger\Thrift\Agent\Zipkin\BinaryAnnotation;
use OpenTracing\Formats;
use OpenTracing\Tracer;
use Umbrellio\Jaravel\Middleware\JobTracingMiddleware;
use Umbrellio\Jaravel\Services\Job\JobInjectionMaker;
use Umbrellio\Jaravel\Services\Job\JobWithTracingInjectionDispatcher;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Tests\JaravelTestCase;
use Umbrellio\Jaravel\Tests\Utils\TestJob;

class JobTracingTest extends JaravelTestCase
{
    public function testJobHandledWithInjection()
    {
        $tracingContextField = JobTracingMiddleware::JOB_TRACING_CONTEXT_FIELD;
        $fakeBus = Bus::fake();
        $bus = new JobWithTracingInjectionDispatcher($fakeBus, $this->app->make(JobInjectionMaker::class));

        $spanCreator = app(SpanCreator::class);
        $tracer = app(Tracer::class);

        $span = $spanCreator->create('Call MyService');

        $traceId = $span->getContext()->getTraceId();

        $bus->dispatch(new TestJob());

        $fakeBus->assertDispatched(TestJob::class,
            fn ($job) => $tracer->extract(Formats\TEXT_MAP, $job->{$tracingContextField})->getTraceId() === (int)$traceId);
    }

    public function testJobMiddlewareWithoutContext()
    {
        $job = new TestJob();
        $middleware = new JobTracingMiddleware();

        $middleware->handle($job, fn () => true);

        $spans = $this->reporter->getSpans();

        $this->assertCount(1, $spans);
        $span = $spans[0];

        $this->assertSame('Job: Umbrellio\Jaravel\Tests\Utils\TestJob', $span->getOperationName());

        $tags = collect($span->getTags())->mapWithKeys(fn (BinaryAnnotation $tag) => [$tag->key => $tag->value]);

        $expectedTags = [
            'type' => 'job',
            'job_class' => 'Umbrellio\Jaravel\Tests\Utils\TestJob',
        ];

        $this->assertSame($expectedTags, $tags->intersect($expectedTags)->toArray());
    }

    public function testJobMiddlewareWithContext()
    {
        $injectionMaker = $this->app->make(JobInjectionMaker::class);

        $job = new TestJob();
        $middleware = new JobTracingMiddleware();
        $spanCreator = app(SpanCreator::class);
        $tracer = $this->app->make(Tracer::class);

        $spanCreator->create('Call MyService');
        $job = $injectionMaker->injectParentSpanToCommand($job);

        $middleware->handle($job, fn () => true);

        optional($tracer->getScopeManager()->getActive())
            ->close();
        $tracer->flush();

        $spans = array_reverse($this->reporter->getSpans());

        $this->assertCount(2, $spans);

        $serviceSpan = $spans[0];
        $jobSpan = $spans[1];

        $this->assertSame('Call MyService', $serviceSpan->getOperationName());
        $this->assertSame('Job: Umbrellio\Jaravel\Tests\Utils\TestJob', $jobSpan->getOperationName());

        $this->assertSame(
            $serviceSpan->getContext()->getSpanId(), $jobSpan->getContext()->getParentId()
        );
    }

}
