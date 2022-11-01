<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use Illuminate\Support\Facades\Log;
use OpenTelemetry\SDK\Trace\ImmutableSpan;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Tests\JaravelTestCase;

class LoggingWhileTracingTest extends JaravelTestCase
{
    public function testLogsAddedWhenEnabledOption()
    {
        /** @var SpanCreator $spanCreator */
        $spanCreator = $this->app->make(SpanCreator::class);
        $span = $spanCreator->create('Call MyService');
        $scope = $span->activate();

        Log::info('test log', ['context']);

        $span->end();
        $scope->detach();

        $spans = $this->reporter->getSpans();

        $this->assertCount(1, $spans);
        /** @var ImmutableSpan $span */
        $span = $spans[0];

        $this->assertCount(1, $span->getEvents());
        $this->assertSame([
            'message' => 'test log',
            'context' => ['context'],
            'level' => 'info',
        ], $span->getEvents()[0]->getAttributes()->toArray());
    }
}
