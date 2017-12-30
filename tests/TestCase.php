<?php

namespace Pact;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function expectCallableExactly($amount)
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->exactly($amount))
            ->method('__invoke');

        return $mock;
    }

    public function expectCallableOnce()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke');

        return $mock;
    }

    public function expectCallableNever()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->never())
            ->method('__invoke');

        return $mock;
    }

    public function createCallableMock()
    {
        return $this->getMockBuilder('\stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
    }

    public function invalidCallbackDataProvider()
    {
        return array(
            'empty string' => array('', 'string'),
            'object'       => array(new \stdClass, 'stdClass'),
            'array'        => array(array(), 'array'),
            'true'         => array(true, 'boolean'),
            'false'        => array(false, 'boolean'),
            'truthy'       => array(1, 'integer'),
            'falsey'       => array(0, 'integer')
        );
    }
}
