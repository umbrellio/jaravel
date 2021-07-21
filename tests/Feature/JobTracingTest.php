<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use Illuminate\Support\Facades\Bus;
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
        $context = $span->getContext()
            ->buildString();

        $bus->dispatch(new TestJob());

        $fakeBus->assertDispatched(
            TestJob::class,
            fn ($job) => $tracer->extract(Formats\TEXT_MAP, $job->{$tracingContextField})
                ->buildString()
                === $context
        );
    }


    public function testJobMiddlewareWithoutContext()
    {
        $job = new TestJob();
        $middleware = new JobTracingMiddleware();

        $middleware->handle($job, fn () => true);

        $spans = $this->reporter->reportedSpans;

        $this->assertCount(1, $spans);
        $span = $spans[0];

        $this->assertSame('Job: Umbrellio\Jaravel\Tests\Utils\TestJob', $span->getOperationName());
        $this->assertSame([
            'type' => 'job',
            'job_class' => 'Umbrellio\Jaravel\Tests\Utils\TestJob',
        ], $span->tags);
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

        optional($tracer->getScopeManager()->getActive())
            ->close();
        $tracer->flush();

        $middleware->handle($job, fn () => true);

        $spans = $this->reporter->reportedSpans;

        $this->assertCount(2, $spans);

        $serviceSpan = $spans[0];
        $jobSpan = $spans[1];

        $this->assertSame('Call MyService', $serviceSpan->getOperationName());
        $this->assertSame('Job: Umbrellio\Jaravel\Tests\Utils\TestJob', $jobSpan->getOperationName());
        $this->assertCount(1, $jobSpan->references);
        $this->assertSame(
            $serviceSpan->getContext()
                ->buildString(),
            $jobSpan->references[0]->getSpanContext()->buildString()
        );
    }

}
