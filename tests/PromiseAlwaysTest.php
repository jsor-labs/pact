<?php

namespace Pact;

class PromiseAlwaysTest extends TestCase
{
    /** @test */
    public function it_does_not_suppress_value()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
        });

        $value = new \stdClass();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($value));

        $promise
            ->always(function () {})
            ->then($mock);

        $resolve($value);
    }

    /** @test */
    public function it_does_not_suppress_value_when_callback_returns_a_non_promise()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
        });

        $value = new \stdClass();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($value));

        $promise
            ->always(function () {
                return 1;
            })
            ->then($mock);

        $resolve($value);
    }

    /** @test */
    public function it_does_not_suppress_value_when_callback_returns_a_promise()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
        });

        $value = new \stdClass();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($value));

        $promise
            ->always(function () {
                return Promise::resolve(1);
            })
            ->then($mock);

        $resolve($value);
    }

    /** @test */
    public function it_rejects_when_callback_throws_an_exception_for_fulfilled_promise()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->always(function () use ($exception) {
                throw $exception;
            })
            ->then(null, $mock);

        $resolve(1);
    }

    /**
     * @test
     * @requires PHP 7
     */
    public function it_rejects_when_callback_throws_an_error_for_fulfilled_promise()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
        });

        $exception = new \Error();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->always(function () use ($exception) {
                throw $exception;
            })
            ->then(null, $mock);

        $resolve(1);
    }

    /** @test */
    public function it_rejects_when_callback_rejects_for_fulfilled_promise()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->always(function () use ($exception) {
                return Promise::reject($exception);
            })
            ->then(null, $mock);

        $resolve(1);
    }

    /** @test */
    public function it_does_not_suppress_reason()
    {
        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->always(function () {})
            ->then(null, $mock);

        $reject($exception);
    }

    /** @test */
    public function it_does_not_suppress_reason_when_callback_returns_a_non_promise()
    {
        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->always(function () {
                return 1;
            })
            ->then(null, $mock);

        $reject($exception);
    }

    /** @test */
    public function it_does_not_suppress_reason_when_callback_returns_a_promise()
    {
        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->always(function () {
                return Promise::resolve(1);
            })
            ->then(null, $mock);

        $reject($exception);
    }

    /** @test */
    public function it_rejects_when_callback_throws_an_exception_for_rejected_promise()
    {
        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->always(function () use ($exception) {
                throw $exception;
            })
            ->then(null, $mock);

        $reject($exception);
    }

    /**
     * @test
     * @requires PHP 7
     */
    public function it_rejects_when_callback_throws_an_error_for_rejected_promise()
    {
        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $exception = new \Error();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->always(function () use ($exception) {
                throw $exception;
            })
            ->then(null, $mock);

        $reject($exception);
    }

    /** @test */
    public function it_rejects_when_callback_rejects_for_rejected_promise()
    {
        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->always(function () use ($exception) {
                return Promise::reject($exception);
            })
            ->then(null, $mock);

        $reject($exception);
    }
}
