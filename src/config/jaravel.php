<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\Job;
use Illuminate\Http\Request;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;
use Umbrellio\Jaravel\Configurations;

return [





    /**
     * Enable Jaravel tracing or not. If not, noop tracer will be used.
     */
    'enabled' => env('JARAVEL_ENABLED', true),
    /**
     * Name of your service, that will be shown in Jaeger panel
     */
    'tracer_name' => env('JARAVEL_TRACER_NAME', 'application'),


    'agent_host' => env('JARAVEL_AGENT_HOST', '127.0.0.1'),

    'agent_port' => env('JARAVEL_AGENT_PORT', 6831),

    /**
     * Host and port (for example: '127.0.0.1:6831') for Jaeger agent
     */
    'agent_host_port' => env('JARAVEL_AGENT_HOST_PORT', '127.0.0.1:6831'),
    /**
     * Header name for trace`s id, that will be responded by TraceIdHttpHeaderMiddleware
     */
    'trace_id_header' => env('JARAVEL_TRACE_ID_HEADER', 'X-Trace-Id'),
    /**
     * Every log in your application will be added to active span, if enabled
     */
    'logs_enabled' => env('JARAVEL_LOGS_ENABLED', true),

    /**
     * Describes configuration for incoming Http requests
     */
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

    /**
     * Describes configuration for console commands
     */
    'console' => [
        'span_name' => fn (string $command, ?InputInterface $input = null) => 'Console: ' . $command,
        'filter_commands' => ['schedule:run', 'horizon', 'queue:'],
        'tags' => fn (string $command, int $exitCode, ?InputInterface $input = null, ?OutputInterface $output = null) => [
            'type' => 'console',
            'console_command' => $command,
            'console_exit_code' => $exitCode,
        ],
    ],

    /**
     * Describes configuration for queued jobs
     */
    'job' => [
        'span_name' => fn ($realJob, ?Job $job) => 'Job: ' . get_class($realJob),
        'tags' => fn ($realJob, ?Job $job) => [
            'type' => 'job',
            'job_class' => get_class($realJob),
            'job_id' => optional($job)
                ->getJobId(),
            'job_connection_name' => optional($job)
                ->getConnectionName(),
            'job_name' => optional($job)
                ->getName(),
            'job_queue' => optional($job)
                ->getQueue(),
            'job_attempts' => optional($job)
                ->attempts(),
        ],
    ],

    /**
     * Describes configuration for Guzzle requests if you`re using middleware created by HttpTracingMiddlewareFactory
     */
    'guzzle' => [
        'span_name' => Configurations\Guzzle\SpanNameResolver::class,
        'tags' => Configurations\Guzzle\TagsResolver::class,
    ],
];
