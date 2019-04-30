<?php

namespace Pact;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function setExpectedException($exceptionName, $exceptionMessage = '', $exceptionCode = null)
    {
        if (!\method_exists($this, 'expectException')) {
            parent::setExpectedException(
                $exceptionName,
                $exceptionMessage,
                $exceptionCode
            );
            return;
        }

        $this->expectException($exceptionName);

        if ('' !== $exceptionMessage) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        if (null !== $exceptionCode) {
            $this->expectExceptionCode($exceptionCode);
        }
    }

    public function setExpectedExceptionRegExp($exceptionName, $exceptionMessageRegExp = '', $exceptionCode = null)
    {
        if (!\method_exists($this, 'expectExceptionMessageRegExp')) {
            parent::setExpectedExceptionRegExp(
                $exceptionName,
                $exceptionMessageRegExp,
                $exceptionCode
            );
            return;
        }

        $this->expectException($exceptionName);

        if ('' !== $exceptionMessageRegExp) {
            $this->expectExceptionMessageRegExp($exceptionMessageRegExp);
        }

        if (null !== $exceptionCode) {
            $this->expectExceptionCode($exceptionCode);
        }
    }

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
            ->setMethods(['__invoke'])
            ->getMock();
    }

    public function invalidReasonProvider()
    {
        return [
            'string' => ['foo', 'string', '"foo"'],
            'empty string' => ['', 'string', '""'],
            'object' => [new \stdClass, 'instance of stdClass', 'stdClass'],
            'array' => [[], 'array', '<ARRAY>'],
            'true' => [true, 'boolean', '<TRUE>'],
            'false' => [false, 'boolean', '<FALSE>'],
            'integer' => [1, 'integer', '1'],
            'float' => [1.1, 'float', '1.1'],
            'resource' => [\fopen('php://temp', 'r'), 'resource', 'stream'],
            'null' => [null, 'null', '<NULL>'],
        ];
    }
}
