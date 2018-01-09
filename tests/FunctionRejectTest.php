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

    /**
     * @test
     * @dataProvider invalidReasonProvider
     **/
    public function it_throws_for_invalid_rejection_reason($invalidReason, $type)
    {
        if (PHP_VERSION_ID < 70000) {
            $regexp = '/' . preg_quote('Argument 1 passed to Pact\Promise::reject() must be an instance of Exception, ' . $type . ' given, called in ' . __FILE__ . ' on line 62', '/') . '/';
        } else {
            $regexp = '/' . preg_quote('Argument 1 passed to Pact\Promise::reject() must implement interface Throwable, ' . $type . ' given, called in ' . __FILE__ . ' on line 62', '/') . '/';
        }

        $this->setExpectedExceptionRegExp(
            '\TypeError',
            $regexp
        );

        Promise::reject($invalidReason);
    }
}
