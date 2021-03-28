<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use OpenTracing\Tracer;
use Umbrellio\Jaravel\Services\Guzzle\HttpTracingMiddlewareFactory;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Tests\JaravelTestCase;

class GuzzleTracingTest extends JaravelTestCase
{
    public function testJobHandledWithInjection()
    {
        $mock = new MockHandler([new Response(200)]);

        $stack = HandlerStack::create($mock);
        $stack->push(HttpTracingMiddlewareFactory::create());
        $client = new Client([
            'handler' => $stack,
        ]);

        $spanCreator = app(SpanCreator::class);
        $tracer = $this->app->make(Tracer::class);
        $spanCreator->create('Call MyService');

        $client->request('GET', 'https://test.com');

        optional($tracer->getScopeManager()->getActive())
            ->close();
        $tracer->flush();

        $spans = $this->reporter->reportedSpans;

        $this->assertCount(2, $spans);

        $serviceSpan = $spans[0];
        $guzzleSpan = $spans[1];

        $this->assertSame('Call MyService', $serviceSpan->getOperationName());
        $this->assertSame('request test.com', $guzzleSpan->getOperationName());
        $this->assertCount(1, $guzzleSpan->references);
        $this->assertSame(
            $serviceSpan->getContext()
                ->buildString(),
            $guzzleSpan->references[0]->getContext()->buildString()
        );
    }
}
