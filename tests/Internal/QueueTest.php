<?php

namespace Pact\Internal;

use Pact\TestCase;

class QueueTest extends TestCase
{
    /** @test */
    public function it_executes_tasks()
    {
        $queue = new Queue();

        $queue->enqueue($this->expectCallableOnce());
        $queue->enqueue($this->expectCallableOnce());
    }

    /** @test */
    public function it_executes_nested_enqueued_tasks()
    {
        $queue = new Queue();

        $nested = $this->expectCallableOnce();

        $task = function () use ($queue, $nested) {
            $queue->enqueue($nested);
        };

        $queue->enqueue($task);
    }

    /**
     * @test
     * @expectedException \Exception
     * @expectedExceptionMessage test
     */
    public function it_rethrows_exceptions_thrown_from_tasks()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(new \Exception('test')));

        $queue = new Queue();
        $queue->enqueue($mock);
    }
}
