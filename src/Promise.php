<?php

namespace Pact;

final class Promise
{
    private const STATE_PENDING = 0;
    private const STATE_FOLLOWING = 1;
    private const STATE_FOLLOWING_FOREIGN = 2;
    private const STATE_FULFILLED = 3;
    private const STATE_REJECTED = 4;

    /**
     * Constant used to explicitly overwrite arguments and references.
     * This ensures that they do not show up in stack traces in PHP 7+.
     */
    private const GC_CLEANUP = '[Pact\Promise::GC_CLEANUP]';

    private $state = Promise::STATE_PENDING;

    private $handlers = [];

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

    /**
     * @param callable|null $resolver
     * @param callable|null $canceller
     */
    public function __construct(callable $resolver = null, callable $canceller = null)
    {
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
    public static function resolve($promiseOrValue = null): self
    {
        if ($promiseOrValue instanceof self) {
            return $promiseOrValue;
        }

        $promise = new Promise();
        $promise->_resolve($promiseOrValue);

        return $promise;
    }

    /**
     * @param \Throwable $reason
     * @return Promise
     */
    public static function reject(\Throwable $reason): self
    {
        $promise = new Promise();
        $promise->_reject($reason);

        return $promise;
    }

    /**
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @return Promise
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null): self
    {
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
    public function always(callable $onSettled): self
    {
        return $this->then(
            static function ($value) use ($onSettled) {
                return Promise::resolve($onSettled())->then(static function () use ($value) {
                    return $value;
                });
            },
            static function (\Throwable $reason) use ($onSettled) {
                return Promise::resolve($onSettled())->then(static function () use ($reason) {
                    return Promise::reject($reason);
                });
            }
        );
    }

    public function cancel(): void
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
                    $parentCanceller = [&$parent, 'cancel'];
                }
            } else {
                // Parent is a foreign promise, check for cancel() is already
                // done in _resolveCallback()
                $parentCanceller = [&$parent, 'cancel'];
            }
        }

        if (null !== $canceller) {
            $this->_resolveFromCallback($canceller);
        }

        // Call the parent canceller after our own canceller
        if ($parentCanceller) {
            $parentCanceller();
        }

        $parent = self::GC_CLEANUP;

        // Must be set after cancellation chain is run
        $this->isCancelled = true;
    }

    public function isFulfilled(): bool
    {
        return self::STATE_FULFILLED === $this->_target()->state;
    }

    public function isRejected(): bool
    {
        return self::STATE_REJECTED === $this->_target()->state;
    }

    public function isPending(): bool
    {
        $target = $this->_target();

        return (
            self::STATE_FULFILLED !== $target->state &&
            self::STATE_REJECTED !== $target->state
        );
    }

    public function isCancelled(): bool
    {
        return $this->isCancelled;
    }

    public function value()
    {
        $target = $this->_target();

        if (self::STATE_FULFILLED === $target->state) {
            return $target->result;
        }

        throw LogicException::valueFromNonFulfilledPromise();
    }

    public function reason(): \Throwable
    {
        $target = $this->_target();

        if (self::STATE_REJECTED === $target->state) {
            return $target->result;
        }

        throw LogicException::reasonFromNonRejectedPromise();
    }

    private function _handle(
        Promise $child,
        callable $onFulfilled = null,
        callable $onRejected = null
    ): void {
        $target = $this->_target();

        if (
            self::STATE_FULFILLED !== $target->state &&
            self::STATE_REJECTED !== $target->state
        ) {
            $target->handlers[] = [$child, $onFulfilled, $onRejected];
            return;
        }

        $isFulfilled = self::STATE_FULFILLED === $target->state;
        $callback = $isFulfilled ? $onFulfilled : $onRejected;
        $result = $target->result;

        self::enqueue(static function () use ($child, $isFulfilled, $callback, $result) {
            if (null === $callback) {
                if ($isFulfilled) {
                    $child->_fulfill($result);
                } else {
                    $child->_reject($result);
                }

                return;
            }

            try {
                $child->_resolve(
                    $callback($result)
                );
            } catch (\Throwable $e) {
                $child->_reject($e);
            }
        });
    }

    private function _resolve($result = null): void
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
            $this->_resolveFromCallback([$result, 'then'], true);

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

    private function _fulfill($value): void
    {
        $this->_settle(self::STATE_FULFILLED, $value);
    }

    private function _reject($reason): void
    {
        $this->_settle(self::STATE_REJECTED, $reason);
    }

    private function _settle($state, $result): void
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

    private function _target(): self
    {
        $target = $this;

        while ($target->result instanceof self) {
            $target = $target->result;
        }

        return $target;
    }

    private function _resolveFromCallback(callable $cb, bool $unblock = false): void
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

            $callback(
                self::resolveFunction($target, $unblock),
                self::rejectFunction($target, $unblock)
            );
        } catch (\Throwable $e) {
            $target = self::GC_CLEANUP;

            if ($unblock) {
                $this->state = self::STATE_PENDING;
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
    private static function resolveFunction(self &$target, bool $unblock): callable
    {
        return static function ($value = null) use (&$target, $unblock) {
            if (!$target instanceof Promise) {
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
    private static function rejectFunction(self &$target, bool $unblock): callable
    {
        // Allow rejecting with non-throwable reasons to ensure
        // interoperability with foreign promise implementations which
        // may allow arbitrary reason types or even rejecting without
        // a reason.
        return static function ($reason = null) use (&$target, $unblock) {
            if (!$target instanceof Promise) {
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

    private static function enqueue(callable $task): void
    {
        static $tasks = [];

        if (1 !== \array_push($tasks, $task)) {
            return;
        }

        for ($i = \key($tasks); isset($tasks[$i]); $i++) {
            $tasks[$i]();
            unset($tasks[$i]);
        }

        $tasks = [];
    }
}
