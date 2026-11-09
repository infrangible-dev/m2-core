<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Test\Unit\Helper;

use Infrangible\Core\Helper\Registry;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests the wrapper of the Magento registry.
 */
class RegistryTest extends TestCase
{
    /**
     * Returns the helper with a real Magento registry.
     *
     * @return Registry
     */
    private function createRegistry(): Registry
    {
        /** @noinspection PhpDeprecationInspection */
        return new Registry(new \Magento\Framework\Registry());
    }

    /**
     * A registered value is returned, unknown keys return null.
     *
     * @return void
     */
    public function testRegisterAndRead(): void
    {
        $registry = $this->createRegistry();

        $registry->register('key', 'value');

        $this->assertSame('value', $registry->registry('key'));
        $this->assertNull($registry->registry('unknown'));
    }

    /**
     * Registering a key twice fails, unless it is graceful (keeps the first value) or overwrites it.
     *
     * @return void
     */
    public function testRegisterTwice(): void
    {
        $registry = $this->createRegistry();

        $registry->register('key', 'first');
        $registry->register('key', 'second', true);

        $this->assertSame('first', $registry->registry('key'));

        $registry->register('key', 'third', false, true);

        $this->assertSame('third', $registry->registry('key'));

        $this->expectException(RuntimeException::class);

        $registry->register('key', 'fourth');
    }

    /**
     * An unregistered key returns null.
     *
     * @return void
     */
    public function testUnregister(): void
    {
        $registry = $this->createRegistry();

        $registry->register('key', 'value');
        $registry->unregister('key');

        $this->assertNull($registry->registry('key'));
    }
}
