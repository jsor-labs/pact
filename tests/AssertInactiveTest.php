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
     * @doesNotPerformAssertions
     * @dataProvider invalidCallbackDataProvider
     */
    public function it_throws_from_constructor_for_invalid_resolver($invalidCallable, $type)
    {
        new Promise($invalidCallable);
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @dataProvider invalidCallbackDataProvider
     */
    public function it_throws_from_constructor_for_invalid_canceller($invalidCallable, $type)
    {
        new Promise(null, $invalidCallable);
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @dataProvider invalidReasonProvider
     **/
    public function it_throws_from_reject_for_invalid_rejection_reason($invalidReason, $type)
    {
        Promise::reject($invalidReason);
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_throws_from_then_for_invalid_fulfillment_callback($invalidCallable, $type)
    {
        $promise = Promise::resolve();
        $promise->then($invalidCallable);
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_throws_from_then_for_invalid_rejection_callback($invalidCallable, $type)
    {
        $promise = Promise::reject(new \Exception());
        $promise->then(null, $invalidCallable);
    }

    /**
     * @test
     * @doesNotPerformAssertions
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_throws_from_always_for_invalid_callback($invalidCallable, $type)
    {
        $promise = Promise::resolve();
        $promise->always($invalidCallable);
    }
}
