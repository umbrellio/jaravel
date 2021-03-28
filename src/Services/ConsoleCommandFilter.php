<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class ConsoleCommandFilter
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function allow(): bool
    {
        $filteredCommands = Config::get('jaravel.console.filter_commands', ['schedule:run', 'horizon', 'queue:']);

        $command = $this->request->server('argv')[1] ?? '';

        return !Str::contains($command, $filteredCommands);
    }
}
