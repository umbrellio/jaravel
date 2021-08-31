<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Configurations\Console;

use Symfony\Component\Console\Input\InputInterface;

class SpanNameResolver
{
    public function __invoke(?string $command, ?InputInterface $input = null): string
    {
        return 'Console: ' . $command;
    }
}
