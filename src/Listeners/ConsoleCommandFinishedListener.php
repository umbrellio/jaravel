<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Listeners;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Config;
use OpenTelemetry\SDK\Trace\Span;
use Umbrellio\Jaravel\Services\Caller;
use Umbrellio\Jaravel\Services\Span\SpanTagHelper;

class ConsoleCommandFinishedListener
{
    public function handle(CommandFinished $event): void
    {
        $span = Span::getCurrent();
        $scope = $span->activate();

        $callableConfig = Config::get('jaravel.console.tags', fn () => [
            'type' => 'console',
        ]);

        SpanTagHelper::setTags(
            $span,
            Caller::call($callableConfig, [$event->command, $event->exitCode, $event->input, $event->output])
        );

        $span->end();
        $scope->detach();
    }
}
