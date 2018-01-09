<?php

namespace Pact;

final class Promise
{
    const STATE_PENDING = 0;
    const STATE_FOLLOWING = 1;
    const STATE_FULFILLED = 2;
    const STATE_REJECTED = 3;

    private $canceller;

    private $state = Promise::STATE_PENDING;

    private $handlers = array();

    private $result;

    /** @var Promise */
    private $parent;

    private $requiredCancelRequests = 0;
    private $isCancelled = false;

    private static $queue;

    /**
     * @param callable|null $resolver
     * @param callable|null $canceller
     * @throws TypeError
     */
    public function __construct($resolver = null, $canceller = null)
    {
        if (null !== $resolver && !\is_callable($resolver)) {
            throw TypeError::createForNonClassTypeHintArgument(
                'Argument 1 passed to %s() must be callable or null, %s given, called in %s on line %s',
                __METHOD__,
                $resolver
            );
        }

        if (null !== $canceller && !\is_callable($canceller)) {
            throw TypeError::createForNonClassTypeHintArgument(
                'Argument 2 passed to %s() must be callable or null, %s given, called in %s on line %s',
                __METHOD__,
                $canceller
            );
        }

        $this->canceller = $canceller;

        if (null !== $resolver) {
            $this->_call($resolver);
        }
    }

    /**
     * @param Promise|mixed|null $promiseOrValue
     * @return Promise
     */
    public static function resolve($promiseOrValue = null)
    {
        if ($promiseOrValue instanceof Promise) {
            return $promiseOrValue;
        }

        $promise = new Promise();
        $promise->_resolve($promiseOrValue);

        return $promise;
    }

    /**
     * @param \Exception|\Throwable $reason
     * @return Promise
     */
    public static function reject($reason)
    {
        $promise = new Promise();
        $promise->_reject($reason);

        return $promise;
    }

    /**
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @return Promise
     * @throws TypeError
     */
    public function then($onFulfilled = null, $onRejected = null)
    {
        if (null !== $onFulfilled && !is_callable($onFulfilled)) {
            throw TypeError::createForNonClassTypeHintArgument(
                'Argument 1 passed to %s() must be callable or null, %s given, called in %s on line %s',
                __METHOD__,
                $onFulfilled
            );
        }

        if (null !== $onRejected && !is_callable($onRejected)) {
            throw TypeError::createForNonClassTypeHintArgument(
                'Argument 2 passed to %s() must be callable or null, %s given, called in %s on line %s',
                __METHOD__,
                $onRejected
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

    /**
     * @param callable $onSettled
     * @return Promise
     * @throws TypeError
     */
    public function always($onSettled)
    {
        if (!is_callable($onSettled)) {
            throw TypeError::createForNonClassTypeHintArgument(
                'Argument 1 passed to %s() must be callable, %s given, called in %s on line %s',
                __METHOD__,
                $onSettled
            );
        }

        return $this->then(
            function ($value) use ($onSettled) {
                return Promise::resolve($onSettled())->then(function () use ($value) {
                    return $value;
                });
            },
            function ($reason) use ($onSettled) {
                return Promise::resolve($onSettled())->then(function () use ($reason) {
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

        $parent = $this->parent;
        $this->parent = null;

        $parentCanceller = null;

        if ($parent) {
            if ($parent instanceof Promise) {
                $parent->requiredCancelRequests--;

                if ($parent->requiredCancelRequests <= 0) {
                    $parentCanceller = array($parent, 'cancel');
                }
            } else {
                // Parent is a foreign promise, check for cancel() is already
                // done in _resolveCallback()
                $parentCanceller = array($parent, 'cancel');
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

    /**
     * @return bool
     */
    public function isFulfilled()
    {
        return Promise::STATE_FULFILLED === $this->state;
    }

    /**
     * @return bool
     */
    public function isRejected()
    {
        return Promise::STATE_REJECTED === $this->state;
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return (
            //!$this->isCancelled &&
            Promise::STATE_FULFILLED !== $this->state &&
            Promise::STATE_REJECTED !== $this->state
        );
    }

    /**
     * @return bool
     */
    public function isCancelled()
    {
        return $this->isCancelled;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        if (Promise::STATE_FULFILLED === $this->state) {
            return $this->result;
        }

        throw Exception\LogicException::valueFromNonFulfilledPromise();
    }

    /**
     * @return \Exception|\Throwable
     */
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
                    $child->_resolve($result);
                } else {
                    $child->_reject($result);
                }

                return;
            }

            try {
                $child->_resolve(
                    \call_user_func($callback, $result)
                );
            } catch (\Exception $e) {
                $child->_reject($e);
            } catch (\Throwable $e) {
                $child->_reject($e);
            }
        });
    }

    /**
     * @internal
     */
    public function _resolve($result = null)
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
                $this->parent = $result;
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

        $this->state = Promise::STATE_FOLLOWING;

        $result->requiredCancelRequests++;
        $this->parent = $result;

        $target->handlers = \array_merge(
            $target->handlers,
            $this->handlers
        );

        $this->handlers = null;
    }

    private function _fulfill($value)
    {
        $this->_settle(Promise::STATE_FULFILLED, $value);
    }

    /**
     * @internal
     */
    public function _reject($reason)
    {
        if (!$reason instanceof \Throwable && !$reason instanceof \Exception) {
            ErrorHandler::warning(
                Exception\InvalidArgumentException::nonThrowableRejection(
                    $reason
                )
            );
        }
        
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
        $this->parent = null;

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
            $target = $target->parent;
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
                    $that->_resolve($value);
                },
                function ($reason) use ($that) {
                    $that->_reject($reason);
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
