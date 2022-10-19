<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Config as ConfigRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\NoopTracer;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Jaeger\AgentExporter;
use OpenTelemetry\SDK\Common\Time\SystemClock;
use OpenTelemetry\SDK\Common\Util\ShutdownHandler;
use OpenTelemetry\SDK\Trace\Span;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Umbrellio\Jaravel\Listeners\ConsoleCommandFinishedListener;
use Umbrellio\Jaravel\Listeners\ConsoleCommandStartedListener;
use Umbrellio\Jaravel\Services\ConsoleCommandFilter;
use Umbrellio\Jaravel\Services\Job\JobWithTracingInjectionDispatcher;

class JaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $config = __DIR__ . '/config/jaravel.php';

        $this->publishes([
            $config => base_path('config/jaravel.php'),
        ], 'config');

        if (!ConfigRepository::get('jaravel.enabled', false)) {
            $this->configureFakeTracer();

            return;
        }

        $this->configureTracer();

        $this->listenLogs();
        $this->listenConsoleEvents();
        $this->extendJobsDispatcher();
    }

    public function configureFakeTracer(): void
    {
        $this->app->instance(TracerInterface::class, NoopTracer::getInstance());
    }

    public function extendJobsDispatcher(): void
    {
        $dispatcher = $this->app->make(Dispatcher::class);
        $this->app->extend(Dispatcher::class, function () use ($dispatcher) {
            return $this->app->make(JobWithTracingInjectionDispatcher::class, compact('dispatcher'));
        });
    }

    private function configureTracer(): void
    {
        if ($tracerCallable = ConfigRepository::get('jaravel.custom_tracer_callable', null)) {
            $this->app->singleton(TracerInterface::class, $tracerCallable);

            return;
        }

        $host = ConfigRepository::get('jaravel.agent_host', '127.0.0.1');
        $port = ConfigRepository::get('jaravel.agent_port', 6832);
        $tracerName = ConfigRepository::get('jaravel.tracer_name', 'application');
        $exporter = new AgentExporter($tracerName, "{$host}:{$port}");

        $tracerProvider = new TracerProvider(new SimpleSpanProcessor($exporter));
        ShutdownHandler::register([$tracerProvider, 'shutdown']);
        $tracer = $tracerProvider->getTracer($tracerName);

        $this->app->instance(TracerInterface::class, $tracer);
    }

    private function listenLogs(): void
    {
        if (!ConfigRepository::get('jaravel.logs_enabled', true)) {
            return;
        }

        Event::listen(MessageLogged::class, function (MessageLogged $e) {
            $span = Span::getCurrent();

            $span->addEvent('Log', [
                'message' => $e->message,
                'context' => $e->context,
                'level' => $e->level,
            ]);
        });
    }

    private function listenConsoleEvents(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        /** @var ConsoleCommandFilter $filter */
        $filter = $this->app->make(ConsoleCommandFilter::class);

        if (!$filter->allow()) {
            return;
        }

        Event::listen(
            CommandStarting::class,
            ConfigRepository::get('jaravel.console.listeners.started', ConsoleCommandStartedListener::class)
        );

        Event::listen(
            CommandFinished::class,
            ConfigRepository::get('jaravel.console.listeners.finished', ConsoleCommandFinishedListener::class)
        );
    }
}
