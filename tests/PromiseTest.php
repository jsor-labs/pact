<?php

namespace Pact;

use Pact;

class PromiseTest extends TestCase
{
    public function it_allows_construction_without_resolver()
    {
        new Promise();
    }

    /**
     * @test
     * @expectedException Pact\Exception\InvalidArgumentException
     */
    public function it_throws_when_resolver_is_not_a_callable()
    {
        new Promise(false);
    }

    /**
     * @test
     * @expectedException Pact\Exception\InvalidArgumentException
     */
    public function it_throws_when_canceller_is_not_a_callable()
    {
        new Promise(null, false);
    }

    /** @test */
    public function it_supports_deep_nested_promise_chains()
    {
        $deferreds = array();

        for ($i = 0; $i < 250; $i++) {
            $resolve = null;
            $p = new Promise(function($res) use (&$resolve) {
                $resolve = $res;
            });

            $deferreds[] = array(
                'promise' => $p,
                'resolve' => $resolve
            );

            $last = $p;
            for ($j = 0; $j < 250; $j++) {
                $last = $last->then(function ($result) {
                    return $result;
                });
            }
        }

        $p = null;
        foreach ($deferreds as $d) {
            if ($p) {
                call_user_func($d['resolve'], $p);
            }

            $p = $d['promise'];
        }

        call_user_func($deferreds[0]['resolve'], true);

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(true));

        $deferreds[0]['promise']->then($mock);
    }
}
