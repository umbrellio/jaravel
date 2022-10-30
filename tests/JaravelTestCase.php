<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests;

use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenTelemetry\SDK\Common\Util\ShutdownHandler;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Orchestra\Testbench\TestCase;
use Umbrellio\Jaravel\Configurations\Http\SpanNameResolver;
use Umbrellio\Jaravel\JaravelServiceProvider;

abstract class JaravelTestCase extends TestCase
{
    protected InMemoryExporter $reporter;

    /** @param Application $app */
    protected function defineEnvironment($app)
    {
        $this->reporter = new InMemoryExporter();
        $app['config']->set('jaravel', $this->jaravelConfiguration($this->reporter));
    }

    /**
     * @param Application $app
     * @return string[]
     */
    protected function getPackageProviders($app)
    {
        return [JaravelServiceProvider::class];
    }

    private function jaravelConfiguration(SpanExporterInterface $exporter): array
    {
        $tracerProvider = new TracerProvider(new SimpleSpanProcessor($exporter));
        ShutdownHandler::register([$tracerProvider, 'shutdown']);
        $tracer = $tracerProvider->getTracer('In memory tracer');

        return [
            'enabled' => true,
            'tracer_name' => 'application',
            'agent_host' => '127.0.0.1',
            'agent_port' => 6831,
            'logs_enabled' => true,

            'custom_tracer_callable' => fn () => $tracer,

            'http' => [
                'span_name' => SpanNameResolver::class,
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
