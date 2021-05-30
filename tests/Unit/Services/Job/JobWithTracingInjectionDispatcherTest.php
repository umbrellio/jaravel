<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Unit\Services\Job;

use Illuminate\Contracts\Bus\QueueingDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Umbrellio\Jaravel\Services\Job\JobInjectionMaker;
use Umbrellio\Jaravel\Services\Job\JobWithTracingInjectionDispatcher;

class JobWithTracingInjectionDispatcherTest extends TestCase
{
    private MockObject $originalDispatcher;
    private MockObject $jobInjectionMaker;
    private JobWithTracingInjectionDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->originalDispatcher = $this->createMock(QueueingDispatcher::class);
        $this->jobInjectionMaker = $this->createMock(JobInjectionMaker::class);
        $this->dispatcher = new JobWithTracingInjectionDispatcher($this->originalDispatcher, $this->jobInjectionMaker);
    }

    /**
     * @dataProvider provideWrappedMethods
     */
    public function testWrappedMethods(string $method)
    {
        $command = new \stdClass();
        $wrapper = new \stdClass();

        $this->jobInjectionMaker
            ->method('injectParentSpanToCommand')
            ->willReturn($wrapper);

        $this->originalDispatcher
            ->expects($this->once())
            ->method($method)
            ->with($this->equalTo($wrapper));

        $this->dispatcher->{$method}($command);
    }

    /**
     * @dataProvider providePassingMethods
     */
    public function testPassingMethods(string $method, $argument): void
    {
        $this->jobInjectionMaker
            ->expects($this->never())
            ->method('injectParentSpanToCommand');

        $this->originalDispatcher
            ->expects($this->once())
            ->method($method)
            ->with($this->equalTo($argument));

        $this->dispatcher->{$method}($argument);
    }

    public function provideWrappedMethods(): array
    {
        return [['dispatchNow'], ['dispatch'], ['dispatchToQueue'], ['dispatchSync']];
    }

    public function providePassingMethods(): array
    {
        return [
            ['hasCommandHandler', new \stdClass()],
            ['getCommandHandler', new \stdClass()],
            ['pipeThrough', []],
            ['map', []],
            ['findBatch', 'id'],
            ['batch', []],
        ];
    }
}
