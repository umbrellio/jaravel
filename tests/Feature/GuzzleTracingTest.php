<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use OpenTelemetry\SDK\Trace\ImmutableSpan;
use Umbrellio\Jaravel\Services\Guzzle\HttpTracingMiddlewareFactory;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Tests\JaravelTestCase;

class GuzzleTracingTest extends JaravelTestCase
{
    public function testJobHandledWithInjection(): void
    {
        $mock = new MockHandler([new Response(200)]);

        $stack = HandlerStack::create($mock);
        $stack->push(HttpTracingMiddlewareFactory::create());
        $client = new Client([
            'handler' => $stack,
        ]);

        /** @var SpanCreator $spanCreator */
        $spanCreator = app(SpanCreator::class);

        $span = $spanCreator->create('Call MyService');
        $scope = $span->activate();

        $client->request('GET', 'https://test.com');

        $span->end();
        $scope->detach();

        $spans = array_reverse($this->reporter->getSpans());

        $this->assertCount(2, $spans);

        $serviceSpan = $spans[0];
        /** @var ImmutableSpan $guzzleSpan */
        $guzzleSpan = $spans[1];

        $this->assertSame('Call MyService', $serviceSpan->getName());
        $this->assertSame('request test.com', $guzzleSpan->getName());

        $this->assertSame($serviceSpan->getContext() ->getSpanId(), $guzzleSpan->getParentSpanId());
    }
}
