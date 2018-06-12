<?php

namespace Pact;

class RejectionTrackerTest extends TestCase
{
    public function tearDown()
    {
        Promise::disableRejectionTracker();

        parent::tearDown();
    }

    /** @test */
    public function it_tracks_unhandled_rejections()
    {
        $tracker = new RejectionTracker();

        Promise::enableRejectionTracker($tracker);

        Promise::reject(new \Exception());

        $this->assertCount(1, $tracker->extractUnhandledRejections());
    }

    /** @test */
    public function it_tracks_handled_rejections()
    {
        $tracker = new RejectionTracker();

        Promise::enableRejectionTracker($tracker);

        $promise = Promise::reject(new \Exception());

        $promise->then(null, function () {});

        $this->assertCount(0, $tracker->extractUnhandledRejections());
    }

    /** @test */
    public function it_tracks_late_handled_rejections()
    {
        $tracker = new RejectionTracker();

        Promise::enableRejectionTracker($tracker);

        $promise = Promise::reject(new \Exception());

        $this->assertCount(1, $tracker->extractUnhandledRejections());

        $promise->then(null, function () {});

        $this->assertCount(1, $tracker->extractLateHandledRejections());
    }

    /**
     * @test
     * @expectedException \Pact\LogicException
     * @expectedExceptionMessage Cannot disable rejection tracker while it has unhandled rejections.
     */
    public function it_throws_when_disabled_with_unhandled_rejections()
    {
        $tracker = new RejectionTracker();

        Promise::enableRejectionTracker($tracker);

        Promise::reject(new \Exception());

        Promise::disableRejectionTracker();
    }
}
