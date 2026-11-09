<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Test\Unit\Helper;

use Infrangible\Core\Helper\Instances;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;

/**
 * Tests creating objects by class name, which the helpers use for classes of optional Magento modules.
 */
class InstancesTest extends TestCase
{
    /** @var ObjectManagerInterface&MockObject */
    private MockObject $objectManager;

    /** @var Instances */
    private Instances $instances;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(ObjectManagerInterface::class);
        $this->instances = new Instances($this->objectManager);
    }

    /**
     * A new instance is created with the arguments.
     *
     * @return void
     */
    public function testCreateInstance(): void
    {
        $dataObject = new DataObject(['key' => 'value']);

        $this->objectManager->expects($this->once())
            ->method('create')
            ->with(DataObject::class, ['data' => ['key' => 'value']])
            ->willReturn($dataObject);

        $this->assertSame(
            $dataObject,
            $this->instances->createInstance(DataObject::class, ['data' => ['key' => 'value']])
        );
    }

    /**
     * The shared instance is returned.
     *
     * @return void
     */
    public function testGetSharedInstance(): void
    {
        $dataObject = new DataObject();

        $this->objectManager->expects($this->once())
            ->method('get')
            ->with(DataObject::class)
            ->willReturn($dataObject);

        $this->assertSame($dataObject, $this->instances->getSharedInstance(DataObject::class));
    }

    /**
     * A class that does not exist (an optional module that is not installed) leads to a RuntimeException.
     *
     * @return void
     */
    public function testCreateInstanceOfMissingClass(): void
    {
        $this->objectManager->method('create')
            ->willThrowException(new ReflectionException('Class "Magento\Review\Model\Review" does not exist'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Class "Magento\Review\Model\Review" does not exist');

        $this->instances->createInstance(DataObject::class);
    }

    /**
     * A shared instance of a class that does not exist leads to a RuntimeException.
     *
     * @return void
     */
    public function testGetSharedInstanceOfMissingClass(): void
    {
        $this->objectManager->method('get')->willThrowException(new ReflectionException('missing'));

        $this->expectException(RuntimeException::class);

        $this->instances->getSharedInstance(DataObject::class);
    }

    /**
     * An object of another class (for example a wrong preference) leads to a RuntimeException.
     *
     * @return void
     */
    public function testCreateInstanceOfOtherClass(): void
    {
        $this->objectManager->method('create')->willReturn(new \stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not create an instance of class: ' . DataObject::class);

        $this->instances->createInstance(DataObject::class);
    }

    /**
     * The untyped methods return null for values that are not objects.
     *
     * @return void
     */
    public function testGetInstanceAndSingletonOfNonObject(): void
    {
        $this->objectManager->method('create')->willReturn(null);
        $this->objectManager->method('get')->willReturn(null);

        $this->assertNull($this->instances->getInstance(DataObject::class));
        $this->assertNull($this->instances->getSingleton(DataObject::class));
    }
}
