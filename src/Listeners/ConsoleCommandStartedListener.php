<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Listeners;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Config as ConfigRepository;
use Umbrellio\Jaravel\Services\Caller;
use Umbrellio\Jaravel\Services\Span\SpanCreator;

class ConsoleCommandStartedListener
{
    private SpanCreator $spanCreator;

    public function __construct(SpanCreator $spanCreator)
    {
        $this->spanCreator = $spanCreator;
    }

    public function handle(CommandStarting $event): void
    {
        $this->spanCreator
            ->create(
                Caller::call(ConfigRepository::get('jaravel.console.span_name'), [$event->command, $event->input])
            );
    }
}
