<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use Illuminate\Support\Facades\Log;
use OpenTracing\Tracer;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Tests\JaravelTestCase;

class LoggingWhileTracingTest extends JaravelTestCase
{
    public function testLogsAddedWhenEnabledOption()
    {
        $tracer = $this->app->make(Tracer::class);
        $spanCreator = $this->app->make(SpanCreator::class);
        $spanCreator->create('Call MyService');

        Log::info('test log', ['context']);

        optional($tracer->getScopeManager()->getActive())
            ->close();
        $tracer->flush();

        $spans = $this->reporter->reportedSpans;
        $this->assertCount(1, $spans);
        $span = $spans[0];

        $this->assertCount(1, $span->logs);
        $this->assertSame([
            'message' => 'test log',
            'context' => ['context'],
            'level' => 'info',
        ], $span->logs[0]['fields']);
    }
}
