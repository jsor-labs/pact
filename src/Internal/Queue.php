<?php

namespace Pact\Internal;

/**
 * @internal
 */
final class Queue
{
    private $queue = array();

    public function enqueue($task)
    {
        if (1 === \array_push($this->queue, $task)) {
            $this->drain();
        }
    }

    private function drain()
    {
        for ($i = \key($this->queue); isset($this->queue[$i]); $i++) {
            \call_user_func($this->queue[$i]);
            unset($this->queue[$i]);
        }

        $this->queue = array();
    }
}
