<?php

namespace Pact;

use PHPUnit\Framework\MockObject\MockObject;

class PromiseRejectionTrackingTest extends TestCase
{
    public function tearDown()
    {
        Promise::disableRejectionTracker();

        parent::tearDown();
    }

    /** @test */
    public function it_tracks_unhandled_rejections()
    {
        /** @var RejectionTrackerInterface|MockObject $tracker */
        $tracker = $this->getMockBuilder('Pact\RejectionTrackerInterface')
            ->setMethods(['onDisable', 'onReject', 'onHandle'])
            ->getMock();

        $tracker
            ->expects($this->once())
            ->method('onReject');

        $tracker
            ->expects($this->never())
            ->method('onHandle');

        Promise::enableRejectionTracker($tracker);

        Promise::reject(new \Exception());
    }

    /** @test */
    public function it_tracks_unhandled_rejections_from_child()
    {
        /** @var RejectionTrackerInterface|MockObject $tracker */
        $tracker = $this->getMockBuilder('Pact\RejectionTrackerInterface')
            ->setMethods(['onDisable', 'onReject', 'onHandle'])
            ->getMock();

        $tracker
            ->expects($this->once())
            ->method('onReject')
            ->willReturn('id#1');

        $tracker
            ->expects($this->never())
            ->method('onHandle');

        Promise::enableRejectionTracker($tracker);

        /** @var callable $reject */
        $reject = null;
        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $promise->then(function () {});

        $reject(new \Exception());
    }

    /** @test */
    public function it_tracks_late_handled_rejections()
    {
        /** @var RejectionTrackerInterface|MockObject $tracker */
        $tracker = $this->getMockBuilder('Pact\RejectionTrackerInterface')
            ->setMethods(['onDisable', 'onReject', 'onHandle'])
            ->getMock();

        $tracker
            ->expects($this->once())
            ->method('onReject')
            ->willReturn('id#1');

        $tracker
            ->expects($this->once())
            ->method('onHandle')
            ->with('id#1');

        Promise::enableRejectionTracker($tracker);

        $promise = Promise::reject(new \Exception());

        $promise->then(null, function () {});
    }

    /** @test */
    public function it_does_not_track_rejections_with_rejection_handler()
    {
        /** @var RejectionTrackerInterface|MockObject $tracker */
        $tracker = $this->getMockBuilder('Pact\RejectionTrackerInterface')
            ->setMethods(['onDisable', 'onReject', 'onHandle'])
            ->getMock();

        $tracker
            ->expects($this->never())
            ->method('onReject')
            ->willReturn('id#1');

        $tracker
            ->expects($this->never())
            ->method('onHandle')
            ->with('id#1');

        Promise::enableRejectionTracker($tracker);

        /** @var callable $reject */
        $reject = null;
        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $promise->then(null, function () {});

        $reject(new \Exception());
    }

    /** @test */
    public function it_invokes_disable_hook()
    {
        /** @var RejectionTrackerInterface|MockObject $tracker */
        $tracker = $this->getMockBuilder('Pact\RejectionTrackerInterface')
            ->setMethods(['onDisable', 'onReject', 'onHandle'])
            ->getMock();

        $tracker
            ->expects($this->once())
            ->method('onDisable');

        Promise::enableRejectionTracker($tracker);
        Promise::disableRejectionTracker();
    }
}
