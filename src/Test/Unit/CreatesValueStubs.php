<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Test\Unit;

use PHPUnit\Framework\MockObject\Stub;

/**
 * Creates stubs that only return fixed values, like createConfiguredStub() of PHPUnit 10 or newer, which is not
 * available in PHPUnit 9 of Magento 2.4.6 and 2.4.7.
 */
trait CreatesValueStubs
{
    /**
     * Creates a stub of the class whose methods return the given values.
     *
     * @template T of object
     *
     * @param class-string<T> $className
     * @param array $returnValues
     *
     * @phpstan-param array<string, mixed> $returnValues
     *
     * @return T&Stub
     */
    private function createValueStub(string $className, array $returnValues): Stub
    {
        $stub = $this->createStub($className);

        foreach ($returnValues as $methodName => $returnValue) {
            $stub->method($methodName)->willReturn($returnValue);
        }

        return $stub;
    }
}
