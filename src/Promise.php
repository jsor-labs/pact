<?php

namespace Pact;

final class Promise
{
    const STATE_PENDING = 0;
    const STATE_FOLLOWING = 1;
    const STATE_FOLLOWING_FOREIGN = 2;
    const STATE_FULFILLED = 3;
    const STATE_REJECTED = 4;

    /**
     * @internal
     */
    public $state = Promise::STATE_PENDING;

    private $handlers = array();

    /**
     * The result once the promise got resolved.
     *
     * This property holds the topmost `Pact\Promise` in the chain for the time
     * this Promise is following another `Pact\Promise`.
     *
     * Once this Promise is settled, it holds the value (in case of fulfillment)
     * or the reason (in case of rejection).
     *
     * As long as this Promise is unresolved, this property is `null`.
     *
     * @var null|Promise|mixed|\Throwable|\Exception
     */
    private $result;

    /**
     * The parent Promise used for cancellation propagation.
     *
     * This property holds the parent `Pact\Promise` or a foreign Thenable
     * (if the object has a `cancel()` method) for the time this Promise is
     * resolved with another pending Promise.
     *
     * This property is `null` if this Promise is unresolved or after this
     * Promise has been settled or cancelled.
     *
     * @var null|Promise|object
     */
    private $parent;

    /**
     * @var callable|null
     */
    private $canceller;

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
            throw new TypeError(
                TypeError::messageForTypeHintedArgument(
                    'Argument 1 passed to Pact\Promise::__construct() must be callable or null',
                    __METHOD__,
                    $resolver
                )
            );
        }

        if (null !== $canceller && !\is_callable($canceller)) {
            throw new TypeError(
                TypeError::messageForTypeHintedArgument(
                    'Argument 2 passed to Pact\Promise::__construct() must be callable or null',
                    __METHOD__,
                    $canceller
                )
            );
        }

        $this->canceller = $canceller;

        if (null !== $resolver) {
            $this->_resolveFromCallback($resolver);
        }
    }

    /**
     * @param Promise|mixed|null $promiseOrValue
     * @return Promise
     */
    public static function resolve($promiseOrValue = null)
    {
        if ($promiseOrValue instanceof self) {
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
        if (\PHP_VERSION_ID >= 50408) {
            \assert(
                $assertion = \PHP_VERSION_ID >= 70000 ? $reason instanceof \Throwable : $reason instanceof \Exception,
                $assertion ? null : (($desc = TypeError::messageForClassTypeHintedArgument(
                    \PHP_VERSION_ID >= 70000
                        ? 'Argument 1 passed to Pact\Promise::reject() must implement interface Throwable'
                        : 'Argument 1 passed to Pact\Promise::reject() must be an instance of Exception',
                    __METHOD__,
                    $reason
                )) && \PHP_VERSION_ID >= 70000 ? new TypeError($desc) : $desc)
            );
        } else {
            \assert(
                $reason instanceof \Exception
            );
        }

        // Allow rejection with non-throwable reasons in case assertions are disabled
        if (\PHP_VERSION_ID >= 70000) {
            if (!$reason instanceof \Throwable) {
                $reason = ReasonException::createForReason($reason);
            }
        } else {
            if (!$reason instanceof \Exception) {
                $reason = ReasonException::createForReason($reason);
            }
        }

        $promise = new Promise();
        $promise->_reject($reason);

        return $promise;
    }

    /**
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @return Promise
     */
    public function then($onFulfilled = null, $onRejected = null)
    {
        if (\PHP_VERSION_ID >= 50408) {
            \assert(
                $assertion = (null === $onFulfilled || \is_callable($onFulfilled)),
                $assertion ? null : (($desc = TypeError::messageForTypeHintedArgument(
                    'Argument 1 passed to Pact\Promise::then() must be callable or null',
                    __METHOD__,
                    $onFulfilled
                )) && \PHP_VERSION_ID >= 70000 ? new TypeError($desc) : $desc)
            );
        } else {
            \assert(
                null === $onFulfilled || \is_callable($onFulfilled)
            );
        }

        if (\PHP_VERSION_ID >= 50408) {
            \assert(
                $assertion = (null === $onRejected || \is_callable($onRejected)),
                $assertion ? null : (($desc = TypeError::messageForTypeHintedArgument(
                    'Argument 2 passed to Pact\Promise::then() must be callable or null',
                    __METHOD__,
                    $onRejected
                )) && \PHP_VERSION_ID >= 70000 ? new TypeError($desc) : $desc)
            );
        } else {
            \assert(
                null === $onRejected || \is_callable($onRejected)
            );
        }

        $this->requiredCancelRequests++;

        $child = new Promise();
        $child->parent = $this;

        $this->_handle($child, $onFulfilled, $onRejected);

        return $child;
    }

    /**
     * @param callable $onSettled
     * @return Promise
     */
    public function always($onSettled)
    {
        if (\PHP_VERSION_ID >= 50408) {
            \assert(
                $assertion = \is_callable($onSettled),
                $assertion ? null : (($desc = TypeError::messageForTypeHintedArgument(
                    'Argument 1 passed to Pact\Promise::always() must be callable',
                    __METHOD__,
                    $onSettled
                )) && \PHP_VERSION_ID >= 70000 ? new TypeError($desc) : $desc)
            );
        } else {
            \assert(
                \is_callable($onSettled)
            );
        }

        return $this->then(
            function ($value) use ($onSettled) {
                if (!\is_callable($onSettled)) {
                    return $value;
                }

                return Promise::resolve($onSettled())->then(function () use ($value) {
                    return $value;
                });
            },
            function ($reason) use ($onSettled) {
                if (!\is_callable($onSettled)) {
                    return Promise::reject($reason);
                }

                return Promise::resolve($onSettled())->then(function () use ($reason) {
                    return Promise::reject($reason);
                });
            }
        );
    }

    public function cancel()
    {
        if (
            self::STATE_FULFILLED === $this->state ||
            self::STATE_REJECTED === $this->state
        ) {
            return;
        }

        $canceller = $this->canceller;
        $this->canceller = null;

        $parent = $this->parent;
        $this->parent = null;

        $parentCanceller = null;

        if ($parent) {
            if ($parent instanceof self) {
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
            $this->_resolveFromCallback($canceller);
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
        return self::STATE_FULFILLED === $this->state;
    }

    /**
     * @return bool
     */
    public function isRejected()
    {
        return self::STATE_REJECTED === $this->state;
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return (
            //!$this->isCancelled &&
            self::STATE_FULFILLED !== $this->state &&
            self::STATE_REJECTED !== $this->state
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
     * @throws LogicException
     */
    public function value()
    {
        if (self::STATE_FULFILLED === $this->state) {
            return $this->result;
        }

        throw LogicException::valueFromNonFulfilledPromise();
    }

    /**
     * @return \Exception|\Throwable
     * @throws LogicException
     */
    public function reason()
    {
        if (self::STATE_REJECTED === $this->state) {
            return $this->result;
        }

        throw LogicException::reasonFromNonRejectedPromise();
    }

    private function _handle(
        Promise $child,
        $onFulfilled = null,
        $onRejected = null
    ) {
        $target = $this->_target();

        if (
            self::STATE_FULFILLED !== $target->state &&
            self::STATE_REJECTED !== $target->state
        ) {
            $target->handlers[] = array($child, $onFulfilled, $onRejected);
            return;
        }

        $isFulfilled = self::STATE_FULFILLED === $target->state;
        $callback = $isFulfilled ? $onFulfilled : $onRejected;
        $result = $target->result;

        self::enqueue(function () use ($child, $isFulfilled, $callback, $result) {
            if (!\is_callable($callback)) {
                if ($isFulfilled) {
                    $child->_fulfill($result);
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
        if (self::STATE_PENDING !== $this->state) {
            return;
        }

        if (!\is_object($result) || !\method_exists($result, 'then')) {
            $this->_fulfill($result);
            return;
        }

        if ($this === $result) {
            $this->_reject(
                LogicException::circularResolution()
            );
            return;
        }

        if (!$result instanceof self) {
            if (\method_exists($result, 'cancel')) {
                $this->parent = $result;
            }

            $this->state = self::STATE_FOLLOWING_FOREIGN;
            $this->_resolveFromCallback(array($result, 'then'), true);

            return;
        }

        $target = $result->_target();

        if ($this === $target) {
            $this->_reject(
                LogicException::circularResolution()
            );
            return;
        }

        if (self::STATE_FULFILLED === $target->state) {
            $this->_fulfill($target->result);
            return;
        }

        if (self::STATE_REJECTED === $target->state) {
            $this->_reject($target->result);
            return;
        }

        $this->state = self::STATE_FOLLOWING;
        $this->result = $target;

        $result->requiredCancelRequests++;
        $this->parent = $result;

        $target->handlers = \array_merge(
            $target->handlers,
            $this->handlers
        );

        $this->handlers = null;
    }

    /**
     * @internal
     */
    public function _fulfill($value)
    {
        $this->_settle(self::STATE_FULFILLED, $value);
    }

    /**
     * @internal
     */
    public function _reject($reason)
    {
        $this->_settle(self::STATE_REJECTED, $reason);
    }

    private function _settle($state, $result)
    {
        if (self::STATE_PENDING !== $this->state) {
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

        while (self::STATE_FOLLOWING === $target->state) {
            $target = $target->result;
        }

        return $target;
    }

    private function _resolveFromCallback($callback, $unblock = false)
    {
        $that = $this;

        try {
            \call_user_func(
                $callback,
                function ($value = null) use ($that, $unblock) {
                    if ($unblock) {
                        $that->state = Promise::STATE_PENDING;
                    }

                    $that->_resolve($value);
                },
                // Allow rejecting with non-throwable reasons to ensure
                // interoperability with foreign promise implementations which
                // may allow arbitrary reason types or even rejecting without
                // a reason.
                function ($reason = null) use ($that, $unblock) {
                    if (null === $reason) {
                        if (0 === \func_num_args()) {
                            $reason = ReasonException::createWithoutReason();
                        } else {
                            $reason = ReasonException::createForReason(null);
                        }
                    } elseif (!$reason instanceof \Throwable && !$reason instanceof \Exception) {
                        $reason = ReasonException::createForReason($reason);
                    }

                    if ($unblock) {
                        $that->state = Promise::STATE_PENDING;
                    }

                    $that->_reject($reason);
                }
            );
        } catch (\Exception $e) {
            if ($unblock) {
                $this->state = Promise::STATE_PENDING;
            }

            $this->_reject($e);
        } catch (\Throwable $e) {
            if ($unblock) {
                $this->state = Promise::STATE_PENDING;
            }

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
