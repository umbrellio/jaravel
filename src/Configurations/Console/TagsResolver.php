<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Configurations\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TagsResolver
{
    public function __invoke(
        string $command,
        int $exitCode,
        ?InputInterface $input = null,
        ?OutputInterface $output = null
    ) {
        return [
            'type' => 'console',
            'console_command' => $command,
            'console_exit_code' => $exitCode,
        ];
    }
}
