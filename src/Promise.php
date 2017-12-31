<?php

namespace Pact;

final class Promise
{
    const STATE_PENDING = 0;
    const STATE_FULFILLED = 1;
    const STATE_REJECTED = 2;
    const STATE_FOLLOWING = 3;

    private $canceller;

    private $state = Promise::STATE_PENDING;

    private $handlers = array();

    private $result;

    /** @var Promise */
    private $cancellationParent;

    private $requiredCancelRequests = 0;
    private $isCancelled = false;

    private static $queue;

    public function __construct($resolver = null, $canceller = null)
    {
        if (null !== $resolver && !\is_callable($resolver)) {
            throw Exception\InvalidArgumentException::invalidResolver(
                $resolver
            );
        }

        if (null !== $canceller && !\is_callable($canceller)) {
            throw Exception\InvalidArgumentException::invalidCanceller(
                $resolver
            );
        }

        $this->canceller = $canceller;

        if (null !== $resolver) {
            $this->_call($resolver);
        }
    }

    public static function resolve($promiseOrValue = null)
    {
        if ($promiseOrValue instanceof Promise) {
            return $promiseOrValue;
        }

        $promise = new Promise();
        $promise->_resolveCallback($promiseOrValue);

        return $promise;
    }

    public static function reject($reason)
    {
        $promise = new Promise();
        $promise->_rejectCallback($reason);

        return $promise;
    }

    public function then($onFulfilled = null, $onRejected = null)
    {
        if (null !== $onFulfilled && !is_callable($onFulfilled)) {
            \trigger_error(
                Exception\InvalidArgumentException::invalidThenFulfillmentCallback(
                    $onFulfilled
                ),
                \E_USER_WARNING
            );
        }

        if (null !== $onRejected && !is_callable($onRejected)) {
            \trigger_error(
                Exception\InvalidArgumentException::invalidThenRejectionCallback(
                    $onRejected
                ),
                \E_USER_WARNING
            );
        }

        $canceller = null;

        if (null !== $this->canceller) {
            $this->requiredCancelRequests++;

            $that = $this;
            $requiredCancelRequests =& $this->requiredCancelRequests;

            $canceller = function () use ($that, &$requiredCancelRequests) {
                $requiredCancelRequests--;

                if ($requiredCancelRequests <= 0) {
                    $that->cancel();
                }
            };
        }

        $child = new Promise(null, $canceller);

        $this->_handle($child, $onFulfilled, $onRejected);

        return $child;
    }

    public function always($onFulfilledOrRejected)
    {
        if (!is_callable($onFulfilledOrRejected)) {
            \trigger_error(
                Exception\InvalidArgumentException::invalidAlwaysCallback(
                    $onFulfilledOrRejected
                ),
                \E_USER_WARNING
            );

            return $this;
        }

        return $this->then(
            function ($value) use ($onFulfilledOrRejected) {
                return Promise::resolve($onFulfilledOrRejected())->then(function () use ($value) {
                    return $value;
                });
            },
            function ($reason) use ($onFulfilledOrRejected) {
                return Promise::resolve($onFulfilledOrRejected())->then(function () use ($reason) {
                    return Promise::reject($reason);
                });
            }
        );
    }

    public function cancel()
    {
        if (
            Promise::STATE_FULFILLED === $this->state ||
            Promise::STATE_REJECTED === $this->state
        ) {
            return;
        }

        $canceller = $this->canceller;
        $this->canceller = null;

        $cancellationParent = $this->cancellationParent;
        $this->cancellationParent = null;

        $parentCanceller = null;

        if ($cancellationParent) {
            if ($cancellationParent instanceof Promise) {
                $cancellationParent->requiredCancelRequests--;

                if ($cancellationParent->requiredCancelRequests <= 0) {
                    $parentCanceller = array($cancellationParent, 'cancel');
                }
            } else {
                // Parent is a foreign promise, check for cancel() is already
                // done in _resolveCallback()
                $parentCanceller = array($cancellationParent, 'cancel');
            }
        }

        if (null !== $canceller) {
            $this->_call($canceller);
        }

        // Call the parent canceller after our own canceller
        if ($parentCanceller) {
            \call_user_func($parentCanceller);
        }

        // Must be set after cancellation chain is run
        $this->isCancelled = true;
    }

    public function isFulfilled()
    {
        return Promise::STATE_FULFILLED === $this->state;
    }

    public function isRejected()
    {
        return Promise::STATE_REJECTED === $this->state;
    }

    public function isPending()
    {
        return (
            //!$this->isCancelled &&
            Promise::STATE_FULFILLED !== $this->state &&
            Promise::STATE_REJECTED !== $this->state
        );
    }

    public function isCancelled()
    {
        return $this->isCancelled;
    }

    public function value()
    {
        if (Promise::STATE_FULFILLED === $this->state) {
            return $this->result;
        }

        throw Exception\LogicException::valueFromNonFulfilledPromise();
    }

    public function reason()
    {
        if (Promise::STATE_REJECTED === $this->state) {
            return $this->result;
        }

        throw Exception\LogicException::reasonFromNonRejectedPromise();
    }

    private function _handle(
        Promise $child,
        $onFulfilled = null,
        $onRejected = null
    )
    {
        $parent = $this->_target();

        if (Promise::STATE_PENDING === $parent->state) {
            $parent->handlers[] = array($child, $onFulfilled, $onRejected);
            return;
        }

        $isFulfilled = Promise::STATE_FULFILLED === $parent->state;
        $callback = $isFulfilled ? $onFulfilled : $onRejected;
        $result = $parent->result;

        self::enqueue(function () use ($child, $isFulfilled, $callback, $result) {
            if (!\is_callable($callback)) {
                if ($isFulfilled) {
                    $child->_resolveCallback($result);
                } else {
                    $child->_rejectCallback($result);
                }

                return;
            }

            try {
                $child->_resolveCallback(
                    \call_user_func($callback, $result)
                );
            } catch (\Exception $e) {
                $child->_reject($e);
            } catch (\Throwable $e) {
                $child->_reject($e);
            }
        });
    }

    /** @internal */
    public function _resolveCallback($result = null)
    {
        if (Promise::STATE_PENDING !== $this->state) {
            return;
        }

        if (!\is_object($result) || !\method_exists($result, 'then')) {
            $this->_fulfill($result);
            return;
        }

        if ($this === $result) {
            $this->_reject(
                Exception\LogicException::circularResolution()
            );
            return;
        }

        if (!$result instanceof Promise) {
            if (\method_exists($result, 'cancel')) {
                $this->cancellationParent = $result;
            }

            $this->_call(array($result, 'then'));
            return;
        }

        $target = $result->_target();

        if ($this === $target) {
            $this->_reject(
                Exception\LogicException::circularResolution()
            );
            return;
        }

        if (Promise::STATE_FULFILLED === $target->state) {
            $this->_fulfill($target->result);
            return;
        }

        if (Promise::STATE_REJECTED === $target->state) {
            $this->_reject($target->result);
            return;
        }

        $result->requiredCancelRequests++;
        $this->cancellationParent = $result;

        $target->handlers = \array_merge(
            $target->handlers,
            $this->handlers
        );

        $this->handlers = array();

        $this->state = Promise::STATE_FOLLOWING;
        $this->result = $target;
    }

    /** @internal */
    public function _rejectCallback($reason)
    {
        if (!$reason instanceof \Throwable && !$reason instanceof \Exception) {
            \trigger_error(
                Exception\InvalidArgumentException::nonThrowableRejection(
                    $reason
                ),
                \E_USER_WARNING
            );
        }

        $this->_reject($reason);
    }

    private function _fulfill($value)
    {
        $this->_settle(Promise::STATE_FULFILLED, $value);
    }

    /** @internal */
    public function _reject($reason)
    {
        $this->_settle(Promise::STATE_REJECTED, $reason);
    }

    private function _settle($state, $result)
    {
        if (Promise::STATE_PENDING !== $this->state) {
            return;
        }

        $this->state = $state;
        $this->result = $result;

        $this->canceller = null;

        $handlers = $this->handlers;
        $this->handlers = null;

        if (!$handlers) {
            return;
        }

        foreach ($handlers as $handler) {
            $this->_handle(
                $handler[0],
                $handler[1],
                $handler[2]
            );
        }
    }

    private function _target()
    {
        $target = $this;

        while (Promise::STATE_FOLLOWING === $target->state) {
            $target = $target->result;
        }

        return $target;
    }

    private function _call($callback)
    {
        $that = $this;

        try {
            \call_user_func(
                $callback,
                function ($value = null) use ($that) {
                    $that->_resolveCallback($value);
                },
                function ($reason) use ($that) {
                    $that->_rejectCallback($reason);
                }
            );
        } catch (\Exception $e) {
            $this->_reject($e);
        } catch (\Throwable $e) {
            $this->_reject($e);
        }
    }

    private static function enqueue($task)
    {
        if (!self::$queue) {
            self::$queue = new Internal\Queue();
        }

        self::$queue->enqueue($task);
    }
}
