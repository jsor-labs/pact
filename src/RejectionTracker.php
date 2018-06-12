<?php

namespace Pact;

final class RejectionTracker implements RejectionTrackerInterface
{
    private static $rejectionId = 1;

    private $unhandledRejections = [];
    private $lateHandledRejections = [];

    public function extractUnhandledRejections()
    {
        $rejections = $this->unhandledRejections;
        $this->unhandledRejections = [];

        return $rejections;
    }

    public function extractLateHandledRejections()
    {
        $rejections = $this->lateHandledRejections;
        $this->lateHandledRejections = [];

        return $rejections;
    }

    public function onDisable()
    {
        $hasRejections = !empty($this->unhandledRejections);

        $this->unhandledRejections = [];
        $this->lateHandledRejections = [];

        if (empty($hasRejections)) {
            return;
        }

        throw LogicException::disableRejectionTrackerWithUnhandledRejections();
    }

    public function onReject(Promise $promise)
    {
        $id = self::$rejectionId++;

        $this->unhandledRejections[$id] = [
            'id' => $id,
            'reason' => (string) $promise->reason(),
            'time' => \microtime(true)
        ];

        return $id;
    }

    public function onHandle($id, Promise $promise)
    {
        if (isset($this->unhandledRejections[$id])) {
            unset($this->unhandledRejections[$id]);

            return;
        }

        // This is a possible late handled rejection which already might be
        // extracted with extractUnhandledRejections()
        $this->lateHandledRejections[$id] = [
            'id' => $id,
            'reason' => (string) $promise->reason(),
            'time' => \microtime(true)
        ];
    }
}
