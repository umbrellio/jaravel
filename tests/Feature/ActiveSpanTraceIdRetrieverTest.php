<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use OpenTracing\Tracer;
use Umbrellio\Jaravel\Services\Span\ActiveSpanTraceIdRetriever;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Tests\JaravelTestCase;

class ActiveSpanTraceIdRetrieverTest extends JaravelTestCase
{
    public function testLogsAddedWhenEnabledOption()
    {
        $tracer = $this->app->make(Tracer::class);
        $spanCreator = $this->app->make(SpanCreator::class);
        $retriever = new ActiveSpanTraceIdRetriever($tracer);

        $spanCreator->create('Call MyService');

        $retrievedTrace = $retriever->retrieve();

        optional($tracer->getScopeManager()->getActive())
            ->close();
        $tracer->flush();

        $spans = $this->reporter->reportedSpans;

        $this->assertCount(1, $spans);
        $span = $spans[0];
        $traceId = $span->getContext()
            ->traceIdLowToString();

        $this->assertSame($retrievedTrace, $traceId);
    }
}
