<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Listeners;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Config;
use OpenTelemetry\SDK\Trace\Span;
use Umbrellio\Jaravel\Services\Caller;
use Umbrellio\Jaravel\Services\Span\SpanAttributeHelper;

class ConsoleCommandFinishedListener
{
    public function handle(CommandFinished $event): void
    {
        $span = Span::getCurrent();

        $callableConfig = Config::get('jaravel.console.attributes', fn () => [
            'type' => 'console',
        ]);

        SpanAttributeHelper::setAttributes(
            $span,
            Caller::call($callableConfig, [$event->command, $event->exitCode, $event->input, $event->output])
        );

        $span->activate()->detach();
    }
}
