<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Umbrellio\Jaravel\Services\ConsoleCommandFilter;
use Umbrellio\Jaravel\Tests\JaravelTestCase;

class ConsoleTracingTest extends JaravelTestCase
{
    public function testConsoleHandledWithTags()
    {
        Artisan::command('jaravel:test', fn () => 'OK');
        $this->artisan('jaravel:test')
            ->run();

        $spans = $this->reporter->reportedSpans;

        $this->assertCount(1, $spans);
        $span = $spans[0];

        $this->assertSame('Console: jaravel:test', $span->getOperationName());
        $this->assertSame([
            'type' => 'console',
            'console_command' => 'jaravel:test',
            'console_exit_code' => 0,
        ], $span->tags);
    }

    /**
     * @dataProvider provider
     */
    public function testAllow(array $argv, array $filterCommands, bool $allow)
    {
        $request = new SymfonyRequest([], [], [], [], [], [
            'argv' => $argv,
        ]);

        Config::set('jaravel.console.filter_commands', $filterCommands);

        $filter = new ConsoleCommandFilter(Request::createFromBase($request));

        $this->assertSame($allow, $filter->allow());
    }

    public function provider()
    {
        return [
            [['artisan', 'horizon:work', '--queue=emails'], ['horizon'], false],

            [['artisan', 'schedule:run'], ['schedule:run'], false],

            [['artisan', 'jaravel:command'], ['schedule:run', 'horizon'], true],
        ];
    }
}
