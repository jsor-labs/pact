<?php

namespace Pact;

class AssertInactiveTest extends TestCase
{
    private $assertActive;
    private $assertWarning;

    public function setUp()
    {
        $this->assertActive = \assert_options(ASSERT_ACTIVE, 0);
        $this->assertWarning = \assert_options(ASSERT_WARNING, 1);

        parent::setUp();
    }

    public function tearDown()
    {
        \assert_options(ASSERT_ACTIVE, $this->assertActive);
        \assert_options(ASSERT_WARNING, $this->assertWarning);

        parent::tearDown();
    }

    /**
     * @test
     * @dataProvider invalidReasonProvider
     * @doesNotPerformAssertions
     **/
    public function it_does_not_throw_from_reject_for_invalid_rejection_reason($invalidReason, $type)
    {
        Promise::reject($invalidReason);
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     * @doesNotPerformAssertions
     **/
    public function it_does_not_throw_from_then_for_invalid_fulfillment_callback($invalidCallable, $type)
    {
        $promise = Promise::resolve();
        $promise->then($invalidCallable);
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     * @doesNotPerformAssertions
     **/
    public function it_does_not_throw_from_then_for_invalid_rejection_callback($invalidCallable, $type)
    {
        $promise = Promise::reject(new \Exception());
        $promise->then(null, $invalidCallable);
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     * @doesNotPerformAssertions
     **/
    public function it_does_not_throw_from_always_for_invalid_callback($invalidCallable, $type)
    {
        $promise = Promise::resolve();
        $promise->always($invalidCallable);
    }
}
