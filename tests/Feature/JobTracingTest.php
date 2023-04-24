<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use Illuminate\Support\Facades\Bus;
use OpenTelemetry\API\Trace\AbstractSpan;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\SDK\Trace\ImmutableSpan;
use Umbrellio\Jaravel\Middleware\JobTracingMiddleware;
use Umbrellio\Jaravel\Services\Job\JobInjectionMaker;
use Umbrellio\Jaravel\Services\Job\JobWithTracingInjectionDispatcher;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Tests\JaravelTestCase;
use Umbrellio\Jaravel\Tests\Utils\TestJob;

class JobTracingTest extends JaravelTestCase
{
    private SpanCreator $spanCreator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->spanCreator = $this->app->make(SpanCreator::class);
    }

    public function testJobHandledWithInjection()
    {
        $tracingContextField = JobTracingMiddleware::JOB_TRACING_CONTEXT_FIELD;
        $fakeBus = Bus::fake();
        $bus = new JobWithTracingInjectionDispatcher($fakeBus, $this->app->make(JobInjectionMaker::class));

        $propagator = TraceContextPropagator::getInstance();

        $span = $this->spanCreator->create('Call MyService');

        $traceId = $span->getContext()
            ->getTraceId();

        $bus->dispatch(new TestJob());

        $fakeBus->assertDispatched(TestJob::class, function ($job) use ($propagator, $traceId, $tracingContextField) {
            $span = AbstractSpan::fromContext($propagator->extract($job->{$tracingContextField}));

            return $span->getContext()
                ->getTraceId() === $traceId;
        });
    }

    public function testJobMiddlewareWithoutContext()
    {
        $job = new TestJob();

        $middleware = $this->app->make(JobTracingMiddleware::class);

        $middleware->handle($job, fn () => true);

        $spans = $this->reporter->getSpans();

        $this->assertCount(1, $spans);
        /** @var ImmutableSpan $span */
        $span = $spans[0];

        $this->assertSame('Job: Umbrellio\Jaravel\Tests\Utils\TestJob', $span->getName());

        $tags = collect($span->getAttributes()->toArray());

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

        $middleware = $this->app->make(JobTracingMiddleware::class);

        $span = $this->spanCreator->create('Call MyService');
        $scope = $span->activate();

        $job = $injectionMaker->injectParentSpanToCommand($job);

        $middleware->handle($job, fn () => true);

        $span->end();
        $scope->detach();

        $spans = array_reverse($this->reporter->getSpans());

        $this->assertCount(2, $spans);

        $serviceSpan = $spans[0];
        /** @var ImmutableSpan $jobSpan */
        $jobSpan = $spans[1];

        $this->assertSame('Call MyService', $serviceSpan->getName());
        $this->assertSame('Job: Umbrellio\Jaravel\Tests\Utils\TestJob', $jobSpan->getName());

        $this->assertSame($serviceSpan->getContext() ->getSpanId(), $jobSpan->getParentSpanId());
    }
}
