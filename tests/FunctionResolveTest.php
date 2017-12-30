<?php

namespace Pact;

class FunctionResolveTest extends TestCase
{
    /** @test */
    public function it_resolves_a_value()
    {
        $expected = 123;

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($expected));

        Promise::resolve($expected)
            ->then(
                $mock,
                $this->expectCallableNever()
            );
    }

    /** @test */
    public function it_resolves_a_fulfilled_promise()
    {
        $expected = 123;

        $resolved = Promise::resolve($expected);

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($expected));

        Promise::resolve($resolved)
            ->then(
                $mock,
                $this->expectCallableNever()
            );
    }

    /** @test */
    public function it_resolves_a_thenable()
    {
        $thenable = new SimpleFulfilledTestThenable();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo('foo'));

        Promise::resolve($thenable)
            ->then(
                $mock,
                $this->expectCallableNever()
            );
    }

    /** @test */
    public function it_resolves_a_cancellable_thenable()
    {
        $thenable = new SimpleTestCancellableThenable();

        $promise = Promise::resolve($thenable);
        $promise->cancel();

        $this->assertTrue($thenable->cancelCalled);
    }

    /** @test */
    public function it_resolves_a_rejected_promise()
    {
        $expected = new \Exception();

        $resolved = Promise::reject($expected);

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($expected));

        Promise::resolve($resolved)
            ->then(
                $this->expectCallableNever(),
                $mock
            );
    }
}
