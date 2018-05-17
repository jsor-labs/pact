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
     * Constant used to explicitly overwrite arguments and references.
     * This ensures that they do not show up in stack traces in PHP 7+.
     */
    const GC_CLEANUP = '[Pact\Promise::GC_CLEANUP]';

    /**
     * @internal
     */
    public $state = Promise::STATE_PENDING;

    private $handlers = array();

    /**
     * The result once the promise got resolved.
     *
     * If this Promise is following another `Pact\Promise`, this property holds
     * the topmost `Pact\Promise` in the chain.
     *
     * Otherwise, it holds the value (in case of fulfillment)
     * or the reason (in case of rejection) once this Promise settled.
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
                TypeError::messageForIncorrectArgumentType(
                    'Argument 1 passed to Pact\Promise::__construct() must be callable or null',
                    __METHOD__,
                    $resolver
                )
            );
        }

        if (null !== $canceller && !\is_callable($canceller)) {
            throw new TypeError(
                TypeError::messageForIncorrectArgumentType(
                    'Argument 2 passed to Pact\Promise::__construct() must be callable or null',
                    __METHOD__,
                    $canceller
                )
            );
        }

        $this->canceller = $canceller;

        if (null !== $canceller) {
            $canceller = self::GC_CLEANUP;
        }

        if (null !== $resolver) {
            $cb = $resolver;
            $resolver = self::GC_CLEANUP;
            $this->_resolveFromCallback($cb);
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
                $assertion ? null : (($desc = TypeError::messageForIncorrectArgumentClassType(
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
                $assertion ? null : (($desc = TypeError::messageForIncorrectArgumentType(
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
                $assertion ? null : (($desc = TypeError::messageForIncorrectArgumentType(
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
                $assertion ? null : (($desc = TypeError::messageForIncorrectArgumentType(
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
        if (!$this->isPending()) {
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
                    $parentCanceller = array(&$parent, 'cancel');
                }
            } else {
                // Parent is a foreign promise, check for cancel() is already
                // done in _resolveCallback()
                $parentCanceller = array(&$parent, 'cancel');
            }
        }

        if (null !== $canceller) {
            $this->_resolveFromCallback($canceller);
        }

        // Call the parent canceller after our own canceller
        if ($parentCanceller) {
            \call_user_func($parentCanceller);
        }

        $parent = self::GC_CLEANUP;

        // Must be set after cancellation chain is run
        $this->isCancelled = true;
    }

    /**
     * @return bool
     */
    public function isFulfilled()
    {
        return self::STATE_FULFILLED === $this->_target()->state;
    }

    /**
     * @return bool
     */
    public function isRejected()
    {
        return self::STATE_REJECTED === $this->_target()->state;
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        $target = $this->_target();

        return (
            self::STATE_FULFILLED !== $target->state &&
            self::STATE_REJECTED !== $target->state
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
        $target = $this->_target();

        if (self::STATE_FULFILLED === $target->state) {
            return $target->result;
        }

        throw LogicException::valueFromNonFulfilledPromise();
    }

    /**
     * @return \Exception|\Throwable
     * @throws LogicException
     */
    public function reason()
    {
        $target = $this->_target();

        if (self::STATE_REJECTED === $target->state) {
            return $target->result;
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

        while ($target->result instanceof self) {
            $target = $target->result;
        }

        return $target;
    }

    private function _resolveFromCallback($cb, $unblock = false)
    {
        $callback = $cb;
        $cb = self::GC_CLEANUP;

        // Use reflection to inspect number of arguments expected by this callback.
        // We did some careful benchmarking here: Using reflection to avoid unneeded
        // function arguments is actually faster than blindly passing them.
        // Also, this helps avoiding unnecessary function arguments in the call stack
        // if the callback creates an Exception (creating garbage cycles).
        if (\is_array($callback)) {
            $ref = new \ReflectionMethod($callback[0], $callback[1]);
        } elseif (\is_object($callback) && !$callback instanceof \Closure) {
            $ref = new \ReflectionMethod($callback, '__invoke');
        } else {
            $ref = new \ReflectionFunction($callback);
        }

        $args = $ref->getNumberOfParameters();

        try {
            if ($args === 0) {
                $callback();
                return;
            }

            // Keep a reference to this promise instance for the static
            // resolve/reject functions.
            // See also resolveFunction() and rejectFunction() for more details.
            $target = &$this;

            \call_user_func(
                $callback,
                self::resolveFunction($target, $unblock),
                self::rejectFunction($target, $unblock)
            );
        } catch (\Exception $e) {
            $target = self::GC_CLEANUP;

            if ($unblock) {
                $this->state = Promise::STATE_PENDING;
            }

            $this->_reject($e);
        } catch (\Throwable $e) {
            $target = self::GC_CLEANUP;

            if ($unblock) {
                $this->state = Promise::STATE_PENDING;
            }

            $this->_reject($e);
        }
    }

    /**
     * Creates a static resolver callback that is not bound to a promise instance.
     *
     * Moving the closure creation to a static method allows us to create a
     * callback that is not bound to a promise instance. By passing the target
     * promise instance by reference, we can still execute its resolving logic
     * and still clear this reference when settling the promise. This helps
     * avoiding garbage cycles if any callback creates an Exception.
     *
     * These assumptions are covered by the test suite, so if you ever feel like
     * refactoring this, go ahead, any alternative suggestions are welcome!
     */
    private static function resolveFunction(self &$target, $unblock)
    {
        return function ($value = null) use (&$target, $unblock) {
            if (Promise::GC_CLEANUP === $target) {
                return;
            }

            if ($unblock) {
                $target->state = Promise::STATE_PENDING;
            }

            $target->_resolve($value);
            $target = Promise::GC_CLEANUP;
        };
    }

    /**
     * Creates a static rejection callback that is not bound to a promise instance.
     *
     * Moving the closure creation to a static method allows us to create a
     * callback that is not bound to a promise instance. By passing the target
     * promise instance by reference, we can still execute its rejection logic
     * and still clear this reference when settling the promise. This helps
     * avoiding garbage cycles if any callback creates an Exception.
     *
     * These assumptions are covered by the test suite, so if you ever feel like
     * refactoring this, go ahead, any alternative suggestions are welcome!
     */
    private static function rejectFunction(self &$target, $unblock)
    {
        // Allow rejecting with non-throwable reasons to ensure
        // interoperability with foreign promise implementations which
        // may allow arbitrary reason types or even rejecting without
        // a reason.
        return function ($reason = null) use (&$target, $unblock) {
            if (Promise::GC_CLEANUP === $target) {
                return;
            }

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
                $target->state = Promise::STATE_PENDING;
            }

            $target->_reject($reason);
            $target = Promise::GC_CLEANUP;
        };
    }

    private static function enqueue($task)
    {
        if (!self::$queue) {
            self::$queue = new Internal\Queue();
        }

        self::$queue->enqueue($task);
    }
}
