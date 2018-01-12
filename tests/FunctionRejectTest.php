<?php

namespace Pact;

class FunctionRejectTest extends TestCase
{
    /** @test */
    public function it_rejects_an_exception()
    {
        $expected = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($expected));

        Promise::reject($expected)
            ->then(
                $this->expectCallableNever(),
                $mock
            );
    }

    /**
     * @test
     * @requires PHP 7
     */
    public function it_rejects_an_error()
    {
        $expected = new \Error();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($expected));

        Promise::reject($expected)
            ->then(
                $this->expectCallableNever(),
                $mock
            );
    }
}
