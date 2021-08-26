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
use Jaeger;
use Jaeger\Config;
use Jaeger\Reporter\InMemoryReporter;
use Jaeger\Sampler\ConstSampler;
use Jaeger\ScopeManager;
use OpenTracing\GlobalTracer;
use OpenTracing\Tracer;
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
        $tracer = new class(
            'fake-tracer',
            new InMemoryReporter(),
            new ConstSampler(),
            true,
            null,
            new ScopeManager()) extends \Jaeger\Tracer {

            protected function getHostName()
            {
                return null;
            }
        };

        $this->app->instance(Tracer::class, $tracer);
    }

    public function extendJobsDispatcher(): void
    {
        $dispatcher = $this->app->make(Dispatcher::class);
        $this->app->extend(Dispatcher::class, function () use ($dispatcher) {
            return $this->app->make(JobWithTracingInjectionDispatcher::class, [
                'dispatcher' => $dispatcher,
            ]);
        });
    }

    private function configureTracer(): void
    {
        if ($tracerCallable = ConfigRepository::get('jaravel.custom_tracer_callable', null)) {
            $this->app->singleton(Tracer::class, $tracerCallable);

            return;
        }

        $config = new Config(
            [
                'sampler' => [
                    'type' => Jaeger\SAMPLER_TYPE_CONST,
                    'param' => true,
                ],
                'logging' => true,
                "local_agent" => [
                    "reporting_host" => ConfigRepository::get('jaravel.agent_host', '127.0.0.1'),
                    "reporting_port" => ConfigRepository::get('jaravel.agent_port', 6832),
                ],
                'trace_id_header' => ConfigRepository::get('jaravel.trace_id_header', 'X-Trace-Id'),
                'dispatch_mode' => Config::JAEGER_OVER_BINARY_UDP,
            ],
            ConfigRepository::get('jaravel.tracer_name', 'application')
        );

        $config->initializeTracer();

        $tracer = GlobalTracer::get();

        $this->app->instance(Tracer::class, $tracer);
    }

    private function listenLogs(): void
    {
        if (!ConfigRepository::get('jaravel.logs_enabled', true)) {
            return;
        }

        Event::listen(MessageLogged::class, function (MessageLogged $e) {
            $span = $this->app->make(Tracer::class)->getActiveSpan();
            if (!$span) {
                return;
            }

            $span->log([
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
