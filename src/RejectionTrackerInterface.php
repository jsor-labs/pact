<?php

namespace Pact;

interface RejectionTrackerInterface
{
    /**
     * This methods will be invoked once a promise gets rejected and has no
     * handlers attached to it.
     *
     * It must return a unique rejection id which will be stored by the promise
     * instance and will be passed back to the onHandle() method when the
     * rejection gets handled at a later point.
     *
     * Note: It is *highly* recommended to *not* store a reference to the
     * Promise object itself which would prevent PHP's garbage collection system
     * from cleaning it up.
     *
     * @param Promise $promise
     * @return int|string A unique rejection id
     */
    public function onReject(Promise $promise);

    /**
     * This method will be invoked once a rejection gets handled by registering
     * a callback via Promise::then().
     *
     * The rejection id passed as first argument is the same as generated from
     * onReject() for the passed promise.
     *
     * Note: It is *highly* recommended to *not* store a reference to the
     * Promise object itself which would prevent PHP's garbage collection system
     * from cleaning it up.
     *
     * @param string The unique rejection id
     * @param Promise $promise
     * @return void
     */
    public function onHandle($id, Promise $promise);

    /**
     * This method will be invoked once the rejection tracker gets disabled via
     * Promise::disableRejectionTracker().
     *
     * This hook can be use for cleanup or, if desired, can throw a fatal error,
     * eg. if the tracker still contains unhandled rejections at this point.
     *
     * @return void
     */
    public function onDisable();
}
