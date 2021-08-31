<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Jaeger\Thrift\Agent\Zipkin\BinaryAnnotation;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Umbrellio\Jaravel\Services\ConsoleCommandFilter;
use Umbrellio\Jaravel\Tests\JaravelTestCase;

class ConsoleTracingTest extends JaravelTestCase
{
    public function testConsoleHandledWithTags(): void
    {
        Artisan::command('jaravel:test', fn () => 'OK');

        $this->artisan('jaravel:test')->run();

        $spans = $this->reporter->getSpans();

        $this->assertCount(1, $spans);
        $span = $spans[0];

        $tags = collect($span->getTags())->mapWithKeys(fn (BinaryAnnotation $tag) => [$tag->key => $tag->value]);

        $expectedTags = [
            'type' => 'console',
            'console_command' => 'jaravel:test',
            'console_exit_code' => 0,
        ];

        $this->assertSame('Console: jaravel:test', $span->getOperationName());
        $this->assertSame($expectedTags, $tags->intersect($expectedTags)->toArray());
    }

    /**
     * @dataProvider provider
     */
    public function testAllow(array $argv, array $filterCommands, bool $allow): void
    {
        $request = new SymfonyRequest([], [], [], [], [], [
            'argv' => $argv,
        ]);

        Config::set('jaravel.console.filter_commands', $filterCommands);

        $filter = new ConsoleCommandFilter(Request::createFromBase($request));

        $this->assertSame($allow, $filter->allow());
    }

    public function provider(): array
    {
        return [
            [['artisan', 'horizon:work', '--queue=emails'], ['horizon'], false],

            [['artisan', 'schedule:run'], ['schedule:run'], false],

            [['artisan', 'jaravel:command'], ['schedule:run', 'horizon'], true],
        ];
    }
}
