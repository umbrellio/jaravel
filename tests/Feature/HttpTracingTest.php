<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use OpenTelemetry\SDK\Trace\ImmutableSpan;
use Umbrellio\Jaravel\Middleware\HttpTracingMiddleware;
use Umbrellio\Jaravel\Tests\JaravelTestCase;

class HttpTracingTest extends JaravelTestCase
{
    public function testHttpHandledWithTags()
    {
        $this->get('/api/jaravel');
        $spans = $this->reporter->getSpans();

        $this->assertCount(1, $spans);
        /** @var ImmutableSpan $span */
        $span = $spans[0];

        $this->assertSame('App: test_route', $span->getName());

        $tags = collect($span->getAttributes()->toArray());

        $expectedTags = [
            'type' => 'http',
            'request_host' => 'localhost',
            'request_path' => 'api/jaravel',
            'request_method' => 'GET',
            'response_status' => '200',
            'error' => '',
        ];

        $this->assertSame($expectedTags, $tags->intersect($expectedTags)->toArray());
    }

    public function testAllowRequestOption()
    {
        Config::set('jaravel.http.allow_request', fn (Request $request) => $request->query->has('allow-tracing'));

        $this->get('/api/jaravel');
        $this->assertEmpty($this->reporter->getSpans());

        $this->get('/api/jaravel?allow-tracing=1');
        $this->assertCount(1, $this->reporter->getSpans());
    }

    public function testDenyRequestOption()
    {
        Config::set('jaravel.http.deny_request', fn (Request $request) => $request->query->has('deny-tracing'));

        $this->get('/api/jaravel?deny-tracing=1');
        $this->assertEmpty($this->reporter->getSpans());

        $this->get('/api/jaravel');
        $this->assertCount(1, $this->reporter->getSpans());
    }

    /** @param Router $router */
    protected function defineRoutes($router)
    {
        $router->get('/api/jaravel', fn () => 'OK')
            ->middleware(HttpTracingMiddleware::class)
            ->name('test_route');
    }
}
