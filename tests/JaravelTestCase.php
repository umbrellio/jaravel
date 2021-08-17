<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests;

use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Jaeger\Reporter\InMemoryReporter;
use Jaeger\Sampler\ConstSampler;
use Jaeger\ScopeManager;
use Jaeger\Tracer;
use Orchestra\Testbench\TestCase;
use Umbrellio\Jaravel\JaravelServiceProvider;

abstract class JaravelTestCase extends TestCase
{
    protected InMemoryReporter $reporter;

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app)
    {
        $this->reporter = new InMemoryReporter();
        $app['config']->set('jaravel', $this->jaravelConfiguration());
    }

    /**
     * @param Application $app
     * @return string[]
     */
    protected function getPackageProviders($app)
    {
        return [JaravelServiceProvider::class];
    }

    private function jaravelConfiguration(): array
    {
        return [
            'enabled' => true,
            'tracer_name' => 'application',
            'agent_host_port' => '127.0.0.1:6831',
            'trace_id_header' => 'X-Trace-Id',
            'logs_enabled' => true,

            'custom_tracer_callable' => fn () => new Tracer(
                'test-tracer',
                $this->reporter,
                new ConstSampler(),
                true,
                null,
                new ScopeManager()),

            'http' => [
                'span_name' => fn (Request $request) => 'App: ' . $request->path(),
                'tags' => fn (Request $request, Response $response) => [
                    'type' => 'http',
                    'request_host' => $request->getHost(),
                    'request_path' => $request->path(),
                    'request_method' => $request->method(),
                    'response_status' => $response->getStatusCode(),
                    'error' => !$response->isSuccessful() && !$response->isRedirection(),
                ],
            ],

            'console' => [
                'span_name' => fn (string $command) => 'Console: ' . $command,
                'filter_commands' => ['schedule:run', 'horizon', 'queue:'],
                'tags' => fn (string $command, int $exitCode) => [
                    'type' => 'console',
                    'console_command' => $command,
                    'console_exit_code' => $exitCode,
                ],
            ],

            'job' => [
                'span_name' => fn ($realJob) => 'Job: ' . get_class($realJob),
                'tags' => fn ($realJob) => [
                    'type' => 'job',
                    'job_class' => get_class($realJob),
                ],
            ],

            'guzzle' => [
                'span_name' => fn (PsrRequest $request) => 'request ' . $request->getUri()->getHost(),
                'tags' => fn (PsrRequest $request) => [
                    'type' => 'request',
                    'uri' => (string) $request->getUri(),
                    'method' => $request->getMethod(),
                    'body' => $request->getBody()
                        ->getContents(),
                ],
            ],
        ];
    }
}
