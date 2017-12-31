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

    /** @test */
    public function it_rejects_a_value_but_triggers_warning()
    {
        $expected = 1;

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($expected));

        $errorCollector = new ErrorCollector();
        $errorCollector->start();

        Promise::reject($expected)
            ->then(
                $this->expectCallableNever(),
                $mock
            );

        $errors = $errorCollector->stop();

        $this->assertEquals(E_USER_WARNING, $errors[0]['errno']);
        $this->assertContains('The rejection reason must be of type \Throwable or \Exception, integer given.', $errors[0]['errstr']);
    }
}
