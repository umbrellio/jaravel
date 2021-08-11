<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use OpenTracing\Tracer;
use Umbrellio\Jaravel\Services\Span\ActiveSpanTraceIdRetriever;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Tests\JaravelTestCase;

class ActiveSpanTraceIdRetrieverTest extends JaravelTestCase
{
    private Tracer $tracer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracer = $this->app->make(Tracer::class);
    }

    public function testLogsAddedWhenEnabledOption()
    {
        $spanCreator = $this->app->make(SpanCreator::class);
        $retriever = new ActiveSpanTraceIdRetriever($this->tracer);

        $spanCreator->create('Call MyService');

        $retrievedTraceId = $retriever->retrieve();

        optional($this->tracer->getScopeManager()->getActive())
            ->close();

        $this->tracer->flush();

        $spans = $this->reporter->getSpans();

        $this->assertCount(1, $spans);
        $span = $spans[0];
        $traceId = $span->getContext()->getTraceId();

        $this->assertSame($retrievedTraceId, $traceId);
    }

    public function testNullIfNoActiveSpan()
    {
        $retriever = new ActiveSpanTraceIdRetriever($this->tracer);

        $retrievedTrace = $retriever->retrieve();

        $this->assertNull($retrievedTrace);
    }
}
