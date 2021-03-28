<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Umbrellio\Jaravel\Middleware\HttpTracingMiddleware;
use Umbrellio\Jaravel\Tests\JaravelTestCase;

class HttpTracingTest extends JaravelTestCase
{
    public function testHttpResponseWithTraceIdHeader()
    {
        $response = $this->get('/api/jaravel');
        $spans = $this->reporter->reportedSpans;

        $this->assertCount(1, $spans);
        $span = $spans[0];
        $traceId = $span->getContext()
            ->traceIdLowToString();

        $response->assertHeader('x-trace-id', $traceId);
    }


    public function testHttpHandledWithTags()
    {
        $this->get('/api/jaravel');
        $spans = $this->reporter->reportedSpans;

        $this->assertCount(1, $spans);
        $span = $spans[0];

        $this->assertSame('App: api/jaravel', $span->getOperationName());
        $this->assertSame([
            'type' => 'http',
            'request_host' => 'localhost',
            'request_path' => 'api/jaravel',
            'request_method' => 'GET',
            'response_status' => 200,
            'error' => false,
        ], $span->tags);
    }



    public function testAllowRequestOption()
    {
        Config::set('jaravel.http.allow_request', fn (Request $request) => $request->query->has('allow-tracing'));

        $this->get('/api/jaravel');
        $this->assertEmpty($this->reporter->reportedSpans);

        $this->get('/api/jaravel?allow-tracing=1');
        $this->assertCount(1, $this->reporter->reportedSpans);
    }


    public function testDenyRequestOption()
    {
        Config::set('jaravel.http.deny_request', fn (Request $request) => $request->query->has('deny-tracing'));

        $this->get('/api/jaravel?deny-tracing=1');
        $this->assertEmpty($this->reporter->reportedSpans);

        $this->get('/api/jaravel');
        $this->assertCount(1, $this->reporter->reportedSpans);
    }
    /**
     * @param Router $router
     */
    protected function defineRoutes($router)
    {
        $router->get('/api/jaravel', fn () => 'OK')
            ->middleware(HttpTracingMiddleware::class);
    }
}
