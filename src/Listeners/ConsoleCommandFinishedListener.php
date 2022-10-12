<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Listeners;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Config;
use OpenTracing\Tracer;
use Umbrellio\Jaravel\Services\Caller;
use Umbrellio\Jaravel\Services\Span\SpanAttributeHelper;

class ConsoleCommandFinishedListener
{
    private Tracer $tracer;

    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
    }

    public function handle(CommandFinished $event): void
    {
        $span = $this->tracer->getActiveSpan();
        if (!$span) {
            return;
        }

        $callableConfig = Config::get('jaravel.console.tags', fn () => [
            'type' => 'console',
        ]);

        SpanAttributeHelper::setAttributes(
            $span,
            Caller::call($callableConfig, [$event->command, $event->exitCode, $event->input, $event->output])
        );

        optional($this->tracer->getScopeManager()->getActive())
            ->close();
        $this->tracer->flush();
    }
}
