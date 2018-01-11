<?php

namespace Pact;

class PromiseThenTest extends TestCase
{
    /** @test */
    public function it_returns_a_promise_from_then_for_resolved_promise()
    {
        $promise = Promise::resolve();

        $this->assertInstanceOf('Pact\Promise', $promise->then());
    }


    /** @test */
    public function it_returns_a_promise_from_then_for_pending_promise()
    {
        $promise = new Promise(function () {});

        $this->assertInstanceOf('Pact\Promise', $promise->then());
    }

    /** @test */
    public function it_allows_null_for_callback_parameters_for_resolved_promise()
    {
        $promise = Promise::resolve();

        $this->assertInstanceOf('Pact\Promise', $promise->then(null, null));
    }

    /** @test */
    public function it_allows_null_for_callback_parameters_for_pending_promise()
    {
        $promise = new Promise(function () {});

        $this->assertInstanceOf('Pact\Promise', $promise->then(null, null));
    }
}
