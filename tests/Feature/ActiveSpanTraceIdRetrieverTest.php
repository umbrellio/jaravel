<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use Umbrellio\Jaravel\Services\Span\ActiveSpanTraceIdRetriever;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Tests\JaravelTestCase;

class ActiveSpanTraceIdRetrieverTest extends JaravelTestCase
{
    public function testLogsAddedWhenEnabledOption()
    {
        $spanCreator = $this->app->make(SpanCreator::class);
        $retriever = new ActiveSpanTraceIdRetriever();

        $span = $spanCreator->create('Call MyService');

        $scope = $span->activate();

        $retrievedTraceId = $retriever->retrieve();

        $span->end();
        $scope->detach();

        $spans = $this->reporter->getSpans();

        $this->assertCount(1, $spans);
        $span = $spans[0];
        $traceId = $span->getContext()
            ->getTraceId();

        $this->assertSame($retrievedTraceId, $traceId);
    }

    public function testNullIfNoActiveSpan()
    {
        $retriever = new ActiveSpanTraceIdRetriever();

        $retrievedTrace = $retriever->retrieve();

        $this->assertNull($retrievedTrace);
    }
}
